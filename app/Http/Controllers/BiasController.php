<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoanDecision;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class BiasController extends Controller
{
    public function index(Request $request)
    {
        $decisions = LoanDecision::with('applicant')->get();

        $items = [];

        foreach ($decisions as $d) {

            $app = $d->applicant;
            if (!$app) continue;

            $item = [
                'years_in_business'     => (int)   ($app->years_in_business ?? 0),
                'annual_revenue'        => (float) ($app->annual_revenue ?? 0),
                'net_profit'            => (float) ($app->net_profit ?? 0),
                'monthly_cashflow'      => (float) ($app->monthly_cashflow ?? 0),
                'existing_liabilities'  => (float) ($app->existing_liabilities ?? 0),
                'credit_score'          => (int)   ($app->credit_score ?? 0),
                'past_default'          => (int)   ($app->past_default ?? 0),
                'financing_amount'      => (float) ($app->financing_amount ?? 0),
                'profit_rate'           => (float) ($app->profit_rate ?? 0),
                'tenure_months'         => (int)   ($app->tenure_months ?? 0),
                'collateral_value'      => (float) ($app->collateral_value ?? 0),

                'business_type'         => $app->business_type,
                'industry_category'     => $app->industry_category,
                'financing_purpose'     => $app->financing_purpose,
                'contract_type'         => $app->contract_type,
                'collateral_type'       => $app->collateral_type,

                'business_type_mixed'         => 0,
                'business_type_non-halal'     => 0,

                'industry_category_manufacturing' => 0,
                'industry_category_retail'        => 0,
                'industry_category_transport'     => 0,
                'industry_category_services'      => 0,

                'financing_purpose_expansion'      => 0,
                'financing_purpose_others'         => 0,
                'financing_purpose_renovation'     => 0,
                'financing_purpose_working_capital'=> 0,

                'contract_type_Murabahah'  => 0,
                'contract_type_Musharakah' => 0,
                'contract_type_Tawarruq'   => 0,

                'collateral_type_property'  => 0,
                'collateral_type_vehicle'   => 0,
                'collateral_type_inventory' => 0,
                'collateral_type_none'      => 0,
            ];

            // BUSINESS TYPE ONE-HOT
            if ($app->business_type === 'mixed') {
                $item['business_type_mixed'] = 1;
            } elseif ($app->business_type === 'non-halal') {
                $item['business_type_non-halal'] = 1;
            }

            // INDUSTRY
            switch ($app->industry_category) {
                case 'manufacturing': $item['industry_category_manufacturing'] = 1; break;
                case 'retail':        $item['industry_category_retail'] = 1; break;
                case 'services':      $item['industry_category_services'] = 1; break;
                case 'transport':     $item['industry_category_transport'] = 1; break;
            }

            // PURPOSE
            switch ($app->financing_purpose) {
                case 'expansion':       $item['financing_purpose_expansion'] = 1; break;
                case 'others':          $item['financing_purpose_others'] = 1; break;
                case 'renovation':      $item['financing_purpose_renovation'] = 1; break;
                case 'working_capital': $item['financing_purpose_working_capital'] = 1; break;
            }

            // CONTRACT
            switch ($app->contract_type) {
                case 'Murabahah': $item['contract_type_Murabahah'] = 1; break;
                case 'Musharakah': $item['contract_type_Musharakah'] = 1; break;
                case 'Tawarruq':   $item['contract_type_Tawarruq'] = 1; break;
            }

            // COLLATERAL
            switch ($app->collateral_type) {
                case 'property':  $item['collateral_type_property'] = 1; break;
                case 'vehicle':   $item['collateral_type_vehicle'] = 1; break;
                case 'inventory': $item['collateral_type_inventory'] = 1; break;
                case 'none':      $item['collateral_type_none'] = 1; break;
            }

            // META
            $item['ai_decision']   = $d->prediction;
            $item['human_decision']= $d->human_decision;
            $item['agreement']     = $d->agreement;
            $item['probability']   = $d->probability;
            $item['bias_score']    = $d->bias_score;

            $items[] = $item;
        }

        if (empty($items)) {
            return view('ai.bias_dashboard', [
                'bias' => [
                    'success' => false,
                    'error'   => 'No applicant data available for bias analysis.',
                ]
            ]);
        }

        // SEND TO FLASK
        $client = new Client(['timeout' => 60]);
        $baseFlask = rtrim(env('AI_SERVICE_URL', 'http://127.0.0.1:5000'), '/');

        try {
            $resp = $client->post($baseFlask . '/bias_report', [
                'json' => ['items' => $items]
            ]);

            $biasResult = json_decode($resp->getBody()->getContents(), true);
        } catch (\Exception $e) {

            Log::error('Bias report fetch failed: ' . $e->getMessage());

            return view('ai.bias_dashboard', [
                'bias' => [
                    'success' => false,
                    'error'   => 'Failed to contact AI service.'
                ]
            ]);
        }

        if (!is_array($biasResult)) {
            $biasResult = [
                'success' => false,
                'error'   => 'Invalid response from AI service.'
            ];
        }

        Log::info("BIAS RAW RESPONSE", $biasResult);

        // ✅ This is AJAX-friendly — your blade handles the wrapper automatically
        return view('ai.bias_dashboard', [
            'bias' => $biasResult,
        ]);
    }
}
