<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            // New realistic features
            $table->integer('credit_score')->nullable()->after('debt_ratio');
            $table->integer('commitment')->nullable()->after('credit_score');
            $table->integer('collateral_value')->nullable()->after('commitment');
            $table->unsignedTinyInteger('age')->nullable()->after('collateral_value');

            $table->enum('employment_type', [
                'unemployed',
                'employed',
                'self-employed',
                'government',
                'contract',
            ])->nullable()->after('age');

            $table->enum('loan_purpose', [
                'business',
                'personal',
                'education',
                'renovation',
            ])->nullable()->after('employment_type');

            // Old fields now optional (for old records only)
            // NOTE: requires doctrine/dbal to use change()
            $table->integer('shariah_score')->nullable()->change();
            $table->decimal('profit_margin', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn([
                'credit_score',
                'commitment',
                'collateral_value',
                'age',
                'employment_type',
                'loan_purpose',
            ]);

            // If you really want to revert:
            // (will also need doctrine/dbal)
            // $table->integer('shariah_score')->nullable(false)->change();
            // $table->decimal('profit_margin', 5, 2)->nullable(false)->change();
        });
    }
};
