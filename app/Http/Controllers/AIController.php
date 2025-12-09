<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use App\Models\Applicant;
use App\Models\LoanDecision;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;

class AIController extends Controller
{
    public function showForm()
    {
        return view('ai.form');
    }

    public function predict(Request $request)
    {
        if (!Auth::check()) {
    abort(403, 'Unauthorized');
}
        // =======================================================
        // 1) VALIDATION
        // =======================================================
        $validated = $request->validate([
            'customer_name'            => 'required|string|max:255',
            'business_name'            => 'required|string|max:255',
            'owner_name'               => 'required|string|max:255',
            'business_registration_no' => 'required|string|max:255',

            'business_type'      => 'required|in:halal,non-halal,mixed',
            'years_in_business'  => 'required|integer|min:0',
            'industry_category'  => 'required|in:F&B,retail,transport,manufacturing,services',

            'annual_revenue'       => 'required|numeric|min:0',
            'net_profit'           => 'required|numeric',
            'monthly_cashflow'     => 'required|numeric|min:0',
            'existing_liabilities' => 'required|numeric|min:0',

            'credit_score'         => 'required|integer|min:300|max:900',
            'past_default'         => 'required|boolean',

            'financing_amount'     => 'required|numeric|min:0',
            'financing_purpose'    => 'required|in:working_capital,equipment,renovation,expansion,others',
            'profit_rate'          => 'required|numeric|min:0|max:50',
            'tenure_months'        => 'required|integer|min:1',

            'contract_type'        => 'required|in:Murabahah,Ijarah,Musharakah,Tawarruq',

            'collateral_value'     => 'required|numeric|min:0',
            'collateral_type'      => 'required|in:none,property,vehicle,inventory,cash',
        ]);

        // =======================================================
        // 2) SAVE APPLICANT
        // =======================================================
        
        $applicant = Auth::user()->applicants()->create($validated);


        // =======================================================
        // 3) PREPARE FEATURES FOR FLASK
        // =======================================================
        $features = [
            'years_in_business'    => (int)$validated['years_in_business'],
            'annual_revenue'       => (float)$validated['annual_revenue'],
            'net_profit'           => (float)$validated['net_profit'],
            'monthly_cashflow'     => (float)$validated['monthly_cashflow'],
            'existing_liabilities' => (float)$validated['existing_liabilities'],
            'credit_score'         => (int)$validated['credit_score'],
            'past_default'         => (int)$validated['past_default'],
            'financing_amount'     => (float)$validated['financing_amount'],
            'profit_rate'          => (float)$validated['profit_rate'],
            'tenure_months'        => (int)$validated['tenure_months'],
            'collateral_value'     => (float)$validated['collateral_value'],

            // one-hot placeholders
            'business_type_mixed'      => 0,
            'business_type_non-halal'  => 0,

            'industry_category_manufacturing' => 0,
            'industry_category_retail'        => 0,
            'industry_category_services'      => 0,
            'industry_category_transport'     => 0,

            'financing_purpose_expansion'       => 0,
            'financing_purpose_others'          => 0,
            'financing_purpose_renovation'      => 0,
            'financing_purpose_working_capital' => 0,

            'contract_type_Murabahah'  => 0,
            'contract_type_Musharakah' => 0,
            'contract_type_Tawarruq'   => 0,

            'collateral_type_inventory' => 0,
            'collateral_type_none'      => 0,
            'collateral_type_property'  => 0,
            'collateral_type_vehicle'   => 0,
        ];

        // Fill one-hot based on selected category
        if ($validated['business_type'] === 'mixed') {
            $features['business_type_mixed'] = 1;
        } elseif ($validated['business_type'] === 'non-halal') {
            $features['business_type_non-halal'] = 1;
        }

        switch ($validated['industry_category']) {
            case 'manufacturing': $features['industry_category_manufacturing'] = 1; break;
            case 'retail':        $features['industry_category_retail']        = 1; break;
            case 'services':      $features['industry_category_services']      = 1; break;
            case 'transport':     $features['industry_category_transport']     = 1; break;
        }

        switch ($validated['financing_purpose']) {
            case 'expansion':       $features['financing_purpose_expansion']       = 1; break;
            case 'others':          $features['financing_purpose_others']          = 1; break;
            case 'renovation':      $features['financing_purpose_renovation']      = 1; break;
            case 'working_capital': $features['financing_purpose_working_capital'] = 1; break;
        }

        switch ($validated['contract_type']) {
            case 'Murabahah':  $features['contract_type_Murabahah']  = 1; break;
            case 'Musharakah': $features['contract_type_Musharakah'] = 1; break;
            case 'Tawarruq':   $features['contract_type_Tawarruq']   = 1; break;
        }

        switch ($validated['collateral_type']) {
            case 'inventory': $features['collateral_type_inventory'] = 1; break;
            case 'none':      $features['collateral_type_none']      = 1; break;
            case 'property':  $features['collateral_type_property']  = 1; break;
            case 'vehicle':   $features['collateral_type_vehicle']   = 1; break;
        }

        // =======================================================
        // 4) CALL FLASK
        // =======================================================
        $client = new Client(['timeout' => 60]);
        $base   = rtrim(env('AI_SERVICE_URL', 'http://127.0.0.1:5000'), '/');

        try {
            $resp   = $client->post("$base/predict", ['json' => $features]);
            $result = json_decode($resp->getBody(), true);
        } catch (\Exception $e) {
            Log::error('AI /predict error: '.$e->getMessage());
            return back()->with('error', 'AI service predict failed.');
        }

        if (!($result['success'] ?? false)) {
            return back()->with('error', 'AI service returned error.');
        }

        try {
            $resp2   = $client->post("$base/explain_detailed", ['json' => $features]);
            $explain = json_decode($resp2->getBody(), true);
        } catch (\Exception $e) {
            Log::warning('AI /explain_detailed error: '.$e->getMessage());
            $explain = null;
        }

        try {
            $resp3   = $client->post("$base/explain_plot", [
                'json' => array_merge($features, ['top_k' => 8]),
            ]);
            $plotJson = json_decode($resp3->getBody(), true);
            $plot     = $plotJson['plot_base64'] ?? null;
        } catch (\Exception $e) {
            Log::warning('AI /explain_plot error: '.$e->getMessage());
            $plot = null;
        }

        // =======================================================
        // 5) Extract useful values
        // =======================================================
        $prediction     = $result['prediction'] ?? null; // 0/1 from model
        $aiDecisionText = $result['ai_decision'] ?? ($prediction == 1 ? 'Approved' : 'Rejected');

        $shapValues   = $explain['shap_values']   ?? $result['explanation'] ?? null;
        $decisionPath = $explain['decision_path'] ?? null;

        // =======================================================
        // 6) HUMAN BENCHMARK (our own deterministic rule engine)
        // =======================================================
        $humanScore = $this->computeHumanBenchmark($applicant); // 0 or 1
        $humanLabel = $humanScore === 1 ? 'Approved' : 'Rejected';

        // agreement: compare human (0/1) with model prediction (0/1 if available)
        $agreement = null;
        if (!is_null($prediction)) {
            $agreement = ((int)$prediction === (int)$humanScore) ? 1 : 0;
        }

        // =======================================================
        // 7) SAVE LoanDecision (ALIGNED TO TABLE)
        // =======================================================
        $loanDecision = LoanDecision::create([
            'applicant_id'       => $applicant->id,

            'ai_decision'        => $aiDecisionText,
            'human_decision'     => $humanLabel,
            'corrected_decision' => null,

            'prediction'         => $prediction,
            'agreement'          => $agreement,

            'shap_values'        => $shapValues,
            'bias_score'         => $result['bias_score']  ?? null,
            'probability'        => $result['probability'] ?? null,

            'explainer_type'        => $explain['explainer_type']      ?? null,
            'model_version'         => $explain['model_version']       ?? null,
            'explain_time_ms'       => $explain['explain_time_ms']     ?? null,
            'explanation_narrative' => $explain['narrative']           ?? null,
            'expected_value'        => $explain['expected_value']      ?? null,
            'decision_path'         => $decisionPath,

            'plot_base64'        => $plot,
        ]);

        // =======================================================
        // 8) RESPONSE
        // =======================================================

        // AJAX → return the rendered decision HTML directly
        if ($request->ajax()) {
            return view('ai.decision_show', [
                'decision'       => $loanDecision,
                // int 0/1 for the blade (it checks === 1 / === 0)
                'human_decision' => $humanScore,
                'agreement'      => $agreement,
                'explain'        => $explain,
                'plot'           => $plot,
            ]);
        }

        // Normal redirect → DashboardController::showDecision will recompute benchmark again
        return redirect()
            ->route('ai.decision_show', $loanDecision->id)
            ->with('success', 'Prediction saved.')
            ->with('result', $result)
            ->with('explain_detail', $explain)
            ->with('plot', $plot);
    }

    public function overrideDecision(Request $request, LoanDecision $decision)
    {
        $validated = $request->validate([
            'corrected_decision' => 'required|in:Approved,Rejected',
        ]);

        $decision->corrected_decision = $validated['corrected_decision'];
        $decision->save();

        // If later you make this form AJAX, this block will return updated partial
        if ($request->ajax()) {
            $decision->load('applicant');

            $applicant  = $decision->applicant;
            $humanScore = $this->computeHumanBenchmark($applicant);
            $agreement  = null;
            if (!is_null($decision->prediction)) {
                $agreement = ((int)$decision->prediction === (int)$humanScore) ? 1 : 0;
            }

            // Rebuild explain data from DB (same idea as DashboardController fallback)
            $shap = $decision->shap_values;
            if (is_string($shap)) {
                $decoded = json_decode($shap, true);
                $shap    = is_array($decoded) ? $decoded : null;
            }

            $decisionPath = $decision->decision_path;
            if (is_string($decisionPath)) {
                $decoded      = json_decode($decisionPath, true);
                $decisionPath = is_array($decoded) ? $decoded : null;
            }

            $explain = [
                'success'        => true,
                'shap_values'    => $shap,
                'narrative'      => $decision->explanation_narrative,
                'decision_path'  => $decisionPath,
                'probability'    => $decision->probability,
                'prediction'     => $decision->ai_decision,
                'explainer_type' => $decision->explainer_type,
                'model_version'  => $decision->model_version,
                'expected_value' => $decision->expected_value,
                'explain_time_ms'=> $decision->explain_time_ms,
            ];

            $plot = $decision->plot_base64;

            return view('ai.decision_show', [
                'decision'       => $decision,
                'human_decision' => $humanScore,
                'agreement'      => $agreement,
                'explain'        => $explain,
                'plot'           => $plot,
            ])->with('success', 'Decision overridden.');
        }

        // Non-AJAX: normal redirect back
        return back()->with('success', 'Decision overridden.');
    }

    // =======================================================
    // 9) HUMAN BENCHMARK LOGIC (copied from DashboardController)
    // =======================================================
    private function computeHumanBenchmark(Applicant $app): int
    {
        $score = 0.0;

        // Revenue-based metrics
        $revenue    = max((float)$app->annual_revenue, 1.0);
        $netProfit  = (float)$app->net_profit;
        $profitMargin = $netProfit / $revenue;

        // Profit margin
        if ($profitMargin >= 0.20) $score += 3;
        elseif ($profitMargin >= 0.10) $score += 2;
        elseif ($profitMargin >= 0.03) $score += 1;
        elseif ($profitMargin < 0)     $score -= 3;
        else                           $score -= 1;

        // Cashflow coverage test
        $principal = (float)$app->financing_amount;
        $rate      = ((float)$app->profit_rate) / 100.0;
        $tenure    = max((int)$app->tenure_months, 1);
        $total     = $principal * (1.0 + $rate);
        $monthlyPayment = $total / $tenure;
        $coverage       = ((float)$app->monthly_cashflow) / max($monthlyPayment, 1.0);

        if ($coverage >= 2.0)      $score += 3;
        elseif ($coverage >= 1.3)  $score += 2;
        elseif ($coverage >= 1.0)  $score += 1;
        else                       $score -= 3;

        // Liabilities
        $liabilityRatio = ((float)$app->existing_liabilities) / $revenue;
        if ($liabilityRatio <= 0.30)      $score += 2;
        elseif ($liabilityRatio <= 0.60)  $score += 1;
        else                              $score -= 2;

        // Credit score
        $cs = (int)$app->credit_score;
        if ($cs >= 750)       $score += 2;
        elseif ($cs >= 650)   $score += 1;
        else                  $score -= 1;

        // Past default
        if ((int)$app->past_default === 1) {
            $score -= 4;
        }

        // Stability / years in business
        $yb = (int)$app->years_in_business;
        if ($yb >= 10)        $score += 2;
        elseif ($yb >= 3)     $score += 1;
        else                  $score -= 1;

        // Financial leverage
        $finRatio = $principal / $revenue;
        if ($finRatio <= 0.50)        $score += 1;
        elseif ($finRatio > 1.50)     $score -= 2;
        elseif ($finRatio > 1.00)     $score -= 1;

        // Collateral coverage
        $collCover = ((float)$app->collateral_value) / max($principal, 1.0);
        if ($collCover >= 1.5)        $score += 2;
        elseif ($collCover >= 0.8)    $score += 1;
        elseif ($app->collateral_type === 'none') {
            $score -= 1;
        }

        // Shariah business type
        if ($app->business_type === 'halal')        $score += 1;
        elseif ($app->business_type === 'non-halal')$score -= 2;

        // Industry preference
        if (in_array($app->industry_category, ['manufacturing', 'services'])) {
            $score += 0.5;
        } else {
            $score -= 0.5;
        }

        // Purpose + contract synergy
        if (
            in_array($app->financing_purpose, ['equipment', 'renovation']) &&
            in_array($app->contract_type, ['Murabahah', 'Ijarah'])
        ) {
            $score += 1.0;
        } elseif (
            in_array($app->financing_purpose, ['working_capital', 'expansion']) &&
            in_array($app->contract_type, ['Musharakah', 'Tawarruq'])
        ) {
            $score += 1.0;
        } else {
            $score -= 0.5;
        }

        // Musharakah penalty for weak profitability
        if ($app->contract_type === 'Musharakah' && $profitMargin < 0.05) {
            $score -= 1.0;
        }

        return $score >= 3 ? 1 : 0;
    }
}
