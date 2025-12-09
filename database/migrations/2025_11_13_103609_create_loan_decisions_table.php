<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            
            $table->boolean('ai_decision'); // 1 = approved, 0 = rejected
            $table->boolean('human_decision')->nullable(); // optional human benchmark
            $table->boolean('corrected_decision')->nullable(); // post-bias correction
            
            $table->json('shap_values')->nullable(); // SHAP explanation in JSON
            $table->float('agreement_rate')->nullable();
            $table->float('bias_score')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_decisions');
    }
};
