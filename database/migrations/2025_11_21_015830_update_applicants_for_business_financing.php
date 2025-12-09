<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateApplicantsForBusinessFinancing extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {

            // ------------------------------
            // REMOVE OLD LOAN FIELDS (only if exist)
            // ------------------------------
            $dropCols = [
                'income', 'loan_amount', 'debt_ratio', 'commitment',
                'age', 'employment_type', 'loan_purpose',
                'shariah_score', 'profit_margin'
            ];

            foreach ($dropCols as $col) {
                if (Schema::hasColumn('applicants', $col)) {
                    $table->dropColumn($col);
                }
            }

            // ------------------------------
            // ADD NEW BUSINESS FINANCING FIELDS (ONLY IF NOT EXIST)
            // ------------------------------

            if (!Schema::hasColumn('applicants', 'business_name')) {
                $table->string('business_name')->nullable();
            }

            if (!Schema::hasColumn('applicants', 'owner_name')) {
                $table->string('owner_name')->nullable();
            }

            if (!Schema::hasColumn('applicants', 'business_registration_no')) {
                $table->string('business_registration_no')->nullable();
            }

            if (!Schema::hasColumn('applicants', 'business_type')) {
                $table->enum('business_type', ['halal', 'non-halal', 'mixed'])->nullable();
            }

            if (!Schema::hasColumn('applicants', 'years_in_business')) {
                $table->unsignedInteger('years_in_business')->nullable();
            }

            if (!Schema::hasColumn('applicants', 'industry_category')) {
                $table->enum('industry_category', ['F&B','retail','transport','manufacturing','services'])->nullable();
            }

            if (!Schema::hasColumn('applicants', 'annual_revenue')) {
                $table->decimal('annual_revenue', 15, 2)->nullable();
            }

            if (!Schema::hasColumn('applicants', 'net_profit')) {
                $table->decimal('net_profit', 15, 2)->nullable();
            }

            if (!Schema::hasColumn('applicants', 'monthly_cashflow')) {
                $table->decimal('monthly_cashflow', 15, 2)->nullable();
            }

            if (!Schema::hasColumn('applicants', 'existing_liabilities')) {
                $table->decimal('existing_liabilities', 15, 2)->nullable();
            }

            if (!Schema::hasColumn('applicants', 'credit_score')) {
                $table->unsignedSmallInteger('credit_score')->nullable();
            }

            if (!Schema::hasColumn('applicants', 'past_default')) {
                $table->boolean('past_default')->default(false);
            }

            if (!Schema::hasColumn('applicants', 'financing_amount')) {
                $table->decimal('financing_amount', 15, 2)->nullable();
            }

            if (!Schema::hasColumn('applicants', 'financing_purpose')) {
                $table->enum('financing_purpose', [
                    'working_capital','equipment','renovation','expansion','others'
                ])->nullable();
            }

            if (!Schema::hasColumn('applicants', 'profit_rate')) {
                $table->decimal('profit_rate', 5, 2)->nullable();
            }

            if (!Schema::hasColumn('applicants', 'tenure_months')) {
                $table->unsignedInteger('tenure_months')->nullable();
            }

            if (!Schema::hasColumn('applicants', 'contract_type')) {
                $table->enum('contract_type', ['Murabahah','Ijarah','Musharakah','Tawarruq'])->nullable();
            }

            if (!Schema::hasColumn('applicants', 'collateral_value')) {
                $table->decimal('collateral_value', 15, 2)->nullable();
            }

            if (!Schema::hasColumn('applicants', 'collateral_type')) {
                $table->string('collateral_type')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {

            $newCols = [
                'business_name', 'owner_name', 'business_registration_no',
                'business_type', 'years_in_business', 'industry_category',
                'annual_revenue', 'net_profit', 'monthly_cashflow',
                'existing_liabilities', 'credit_score', 'past_default',
                'financing_amount', 'financing_purpose', 'profit_rate',
                'tenure_months', 'contract_type', 'collateral_value', 'collateral_type'
            ];

            foreach ($newCols as $col) {
                if (Schema::hasColumn('applicants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
