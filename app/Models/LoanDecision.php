<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanDecision extends Model
{
    protected $fillable = [
    'applicant_id',
    'ai_decision',
    'human_decision',
    'corrected_decision',
    'prediction',
    'agreement',
    'shap_values',
    'bias_score',
    'probability',
    'explainer_type',
    'model_version',
    'explain_time_ms',
    'explanation_narrative',
    'expected_value',
    'decision_path',
    'plot_base64',
];

protected $casts = [
    'ai_decision'        => 'string',
    'human_decision'     => 'string',
    'corrected_decision' => 'string',
    'prediction'         => 'integer',
    'agreement'          => 'integer',
    'shap_values'        => 'array',
    'decision_path'      => 'array',
    'bias_score'         => 'float',
    'probability'        => 'float',
    'expected_value'     => 'float',
    'explain_time_ms'    => 'integer',
];


    public function applicant()
    {
        return $this->belongsTo(\App\Models\Applicant::class, 'applicant_id');
    }
}
