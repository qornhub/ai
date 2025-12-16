<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('applicants', function (Blueprint $table) {
            // Convert INT → DECIMAL for large financial fields
            $table->decimal('annual_revenue', 15, 2)->nullable()->change();
            $table->decimal('net_profit', 15, 2)->nullable()->change();
            $table->decimal('monthly_cashflow', 15, 2)->nullable()->change();
            $table->decimal('existing_liabilities', 15, 2)->nullable()->change();
            $table->decimal('financing_amount', 15, 2)->nullable()->change();
            $table->decimal('profit_rate', 5, 2)->nullable()->change();
            $table->decimal('collateral_value', 15, 2)->nullable()->change();

            // Ensure credit score stays as integer but allow NULL
            $table->integer('credit_score')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('applicants', function (Blueprint $table) {
            // Revert back — INT types (NOT recommended but required for rollback)
            $table->integer('annual_revenue')->nullable()->change();
            $table->integer('net_profit')->nullable()->change();
            $table->integer('monthly_cashflow')->nullable()->change();
            $table->integer('existing_liabilities')->nullable()->change();
            $table->integer('financing_amount')->nullable()->change();
            $table->integer('profit_rate')->nullable()->change();
            $table->integer('collateral_value')->nullable()->change();

            $table->integer('credit_score')->nullable()->change();
        });
    }
};
