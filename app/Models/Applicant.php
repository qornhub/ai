<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class Applicant extends Model
{
    protected $fillable = [
        'user_id',   // ⭐ Add this
        'customer_name',

        // Categorical fields directly stored in DB
        'business_name',
        'owner_name',
        'business_registration_no',
        'business_type',
        'industry_category',
        'financing_purpose',
        'contract_type',
        'collateral_type',

        // Numeric fields used by AI model
        'years_in_business',
        'annual_revenue',
        'net_profit',
        'monthly_cashflow',
        'existing_liabilities',
        'credit_score',
        'past_default',
        'financing_amount',
        'profit_rate',
        'tenure_months',
        'collateral_value'
    ];

    protected $casts = [
        'annual_revenue'       => 'float',
        'net_profit'           => 'float',
        'monthly_cashflow'     => 'float',
        'existing_liabilities' => 'float',
        'credit_score'         => 'integer',
        'past_default'         => 'boolean',
        'financing_amount'     => 'float',
        'profit_rate'          => 'float',
        'tenure_months'        => 'integer',
        'collateral_value'     => 'float',
        'years_in_business'    => 'integer',
    ];

    public function loanDecision()
    {
        return $this->hasOne(LoanDecision::class);
    }

    public function user()
{
    return $this->belongsTo(User::class);
}

}
