<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Applicant;
use App\Models\LoanDecision;
 use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dashboard list + search.
     */
  

public function index(Request $request)
{
    $q = $request->input('q');
    $userId = Auth::id();  // safe for IntelliSense

    $decisions = LoanDecision::with('applicant')
        ->whereHas('applicant', function ($sub) use ($userId) {
            // Only show the logged-in user's applicants
            $sub->where('user_id', $userId);
        })
        ->when($q, function ($query) use ($q, $userId) {
            $query->where(function ($inner) use ($q, $userId) {

                $inner->whereHas('applicant', function ($a) use ($q, $userId) {
                    $a->where('user_id', $userId)
                      ->where('customer_name', 'like', "%$q%");
                });

                // Search by date (yyyy-mm-dd)
                $inner->orWhereDate('created_at', $q);
            });
        })
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    return view('ai.dashboard', compact('decisions', 'q'));
}

    /**
     * Compute human benchmark deterministically (ALWAYS used).
     */
    private function computeHumanBenchmark(Applicant $app)
    {
        $score = 0.0;

        // Revenue-based metrics
        $revenue = max((float)$app->annual_revenue, 1.0);
        $netProfit = (float)$app->net_profit;
        $profitMargin = $netProfit / $revenue;

        // Profit margin
        if ($profitMargin >= 0.20) $score += 3;
        elseif ($profitMargin >= 0.10) $score += 2;
        elseif ($profitMargin >= 0.03) $score += 1;
        elseif ($profitMargin < 0) $score -= 3;
        else $score -= 1;

        // Cashflow coverage test
        $principal = (float)$app->financing_amount;
        $rate = ((float)$app->profit_rate) / 100.0;
        $tenure = max((int)$app->tenure_months, 1);
        $total = $principal * (1.0 + $rate);
        $monthlyPayment = $total / $tenure;
        $coverage = ((float)$app->monthly_cashflow) / max($monthlyPayment, 1.0);

        if ($coverage >= 2.0) $score += 3;
        elseif ($coverage >= 1.3) $score += 2;
        elseif ($coverage >= 1.0) $score += 1;
        else $score -= 3;

        // Liabilities
        $liabilityRatio = ((float)$app->existing_liabilities) / $revenue;
        if ($liabilityRatio <= 0.30) $score += 2;
        elseif ($liabilityRatio <= 0.60) $score += 1;
        else $score -= 2;

        // Credit score
        $cs = (int)$app->credit_score;
        if ($cs >= 750) $score += 2;
        elseif ($cs >= 650) $score += 1;
        else $score -= 1;

        // Past default
        if ((int)$app->past_default === 1) $score -= 4;

        // Stability / years in business
        $yb = (int)$app->years_in_business;
        if ($yb >= 10) $score += 2;
        elseif ($yb >= 3) $score += 1;
        else $score -= 1;

        // Financial leverage
        $finRatio = $principal / $revenue;
        if ($finRatio <= 0.50) $score += 1;
        elseif ($finRatio > 1.50) $score -= 2;
        elseif ($finRatio > 1.00) $score -= 1;

        // Collateral coverage
        $collCover = ((float)$app->collateral_value) / max($principal, 1.0);
        if ($collCover >= 1.5) $score += 2;
        elseif ($collCover >= 0.8) $score += 1;
        elseif ($app->collateral_type === "none") $score -= 1;

        // Shariah business type
        if ($app->business_type === "halal") $score += 1;
        elseif ($app->business_type === "non-halal") $score -= 2;

        // Industry preference
        if (in_array($app->industry_category, ["manufacturing", "services"])) {
            $score += 0.5;
        } else {
            $score -= 0.5;
        }

        // Purpose + contract synergy
        if (
            in_array($app->financing_purpose, ["equipment", "renovation"])
            && in_array($app->contract_type, ["Murabahah", "Ijarah"])
        ) {
            $score += 1.0;
        }
        elseif (
            in_array($app->financing_purpose, ["working_capital", "expansion"])
            && in_array($app->contract_type, ["Musharakah", "Tawarruq"])
        ) {
            $score += 1.0;
        }
        else {
            $score -= 0.5;
        }

        // Musharakah penalty for weak profitability
        if ($app->contract_type === "Musharakah" && $profitMargin < 0.05) {
            $score -= 1.0;
        }

        return $score >= 3 ? 1 : 0;
    }

    /**
     * Show a single decision with SHAP + narrative + benchmark.
     */
   public function showDecision(Request $request, $id)
{
    $decision = LoanDecision::with('applicant')->find($id);

    if (!$decision) {
        return redirect()->route('ai.index')->with('error', 'Decision not found.');
    }

    $app = $decision->applicant;

    // Compute human benchmark
    $humanDecision = $this->computeHumanBenchmark($app);

    // Session explain?
    $sessionExplain = session('explain_detail');
    $explain = null;

    if (!empty($sessionExplain) && ($sessionExplain['success'] ?? false)) {

        if (!empty($sessionExplain['shap_values']) && is_string($sessionExplain['shap_values'])) {
            $tmp = json_decode($sessionExplain['shap_values'], true);
            $sessionExplain['shap_values'] = is_array($tmp) ? $tmp : null;
        }

        $explain = $sessionExplain;
    }

    // Fallback: load from DB
    if (!$explain) {
        $shap = $decision->shap_values;
        if (is_string($shap)) {
            $decoded = json_decode($shap, true);
            $shap = is_array($decoded) ? $decoded : null;
        }

        $decisionPath = $decision->decision_path;
        if (is_string($decisionPath)) {
            $decoded = json_decode($decisionPath, true);
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
    }

    $aiDecision = $decision->ai_decision;
    $agreement = ($aiDecision == $humanDecision) ? 1 : 0;

    $result = [
        'ai_decision' => $decision->ai_decision,
        'probability' => $decision->probability ?? $explain['probability'] ?? null,
        'explanation' => $explain['shap_values'] ?? null,
        'bias_score'  => $decision->bias_score,
        'prediction'  => $aiDecision,
    ];

    $plot = session('plot') ?? ($decision->plot_base64 ?? null);

    // ===============================================
    // AJAX mode → return partial view (NO layout)
    // ===============================================
    if ($request->ajax()) {
        return view('ai.decision_show', [
            'decision'       => $decision,
            'result'         => $result,
            'explain'        => $explain,
            'plot'           => $plot,
            'human_decision' => $humanDecision,
            'agreement'      => $agreement,
        ]);
    }

    // ===============================================
    // FULL PAGE LOAD (normal)
    // ===============================================
    return view('ai.decision_show', [
        'decision'       => $decision,
        'result'         => $result,
        'explain'        => $explain,
        'plot'           => $plot,
        'human_decision' => $humanDecision,
        'agreement'      => $agreement,
    ]);
}


    /**
     * Delete decision + applicant.
     */
    public function delete(LoanDecision $decision)
    {
        if ($decision->applicant) {
            $decision->applicant->delete();
        }

        $decision->delete();

        return redirect()->route('ai.index')->with('success', 'Decision deleted.');
    }
}
