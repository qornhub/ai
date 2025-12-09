<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExplainFieldsToLoanDecisionsTable extends Migration
{
    public function up()
    {
        Schema::table('loan_decisions', function (Blueprint $table) {
            // prediction/probability & bias/metrics
            if (! Schema::hasColumn('loan_decisions', 'probability')) {
                $table->double('probability')->nullable()->after('bias_score');
            }

            // explainer meta
            if (! Schema::hasColumn('loan_decisions', 'explainer_type')) {
                $table->string('explainer_type')->nullable()->after('probability');
            }
            if (! Schema::hasColumn('loan_decisions', 'model_version')) {
                $table->string('model_version')->nullable()->after('explainer_type');
            }
            if (! Schema::hasColumn('loan_decisions', 'explain_time_ms')) {
                $table->integer('explain_time_ms')->nullable()->after('model_version');
            }

            // narrative & expected value
            if (! Schema::hasColumn('loan_decisions', 'explanation_narrative')) {
                $table->text('explanation_narrative')->nullable()->after('explain_time_ms');
            }
            if (! Schema::hasColumn('loan_decisions', 'expected_value')) {
                $table->double('expected_value')->nullable()->after('explanation_narrative');
            }

            // decision path (JSON), shap_values may already exist; keep it if present
            if (! Schema::hasColumn('loan_decisions', 'decision_path')) {
                $table->json('decision_path')->nullable()->after('expected_value');
            }

            // store static plot PNG base64 (optional)
            if (! Schema::hasColumn('loan_decisions', 'plot_base64')) {
                // longText because base64 can be large
                $table->longText('plot_base64')->nullable()->after('decision_path');
            }

            // If you want to persist raw features or probability breakdown, add here as needed.
        });
    }

    public function down()
    {
        Schema::table('loan_decisions', function (Blueprint $table) {
            foreach ([
                'plot_base64',
                'decision_path',
                'expected_value',
                'explanation_narrative',
                'explain_time_ms',
                'model_version',
                'explainer_type',
                'probability',
            ] as $col) {
                if (Schema::hasColumn('loan_decisions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
