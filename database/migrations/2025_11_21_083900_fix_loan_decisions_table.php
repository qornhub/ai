<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('loan_decisions', function (Blueprint $table) {

            // Change TINYINT to VARCHAR
            $table->string('ai_decision')->change();
            $table->string('human_decision')->nullable()->change();
            $table->string('corrected_decision')->nullable()->change();

            // Add missing fields
            if (!Schema::hasColumn('loan_decisions', 'prediction')) {
                $table->integer('prediction')->nullable()->after('corrected_decision');
            }

            if (!Schema::hasColumn('loan_decisions', 'agreement')) {
                $table->integer('agreement')->nullable()->after('prediction');
            }

            // Optional: drop outdated column
            if (Schema::hasColumn('loan_decisions', 'agreement_rate')) {
                $table->dropColumn('agreement_rate');
            }
        });
    }

    public function down()
    {
        Schema::table('loan_decisions', function (Blueprint $table) {
            // Reverse changes
            $table->tinyInteger('ai_decision')->change();
            $table->tinyInteger('human_decision')->nullable()->change();
            $table->tinyInteger('corrected_decision')->nullable()->change();

            $table->dropColumn('prediction');
            $table->dropColumn('agreement');

            $table->double('agreement_rate')->nullable();
        });
    }
};
