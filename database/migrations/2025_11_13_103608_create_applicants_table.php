<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->integer('income');
            $table->integer('loan_amount');
            $table->decimal('debt_ratio', 5, 2);
            $table->enum('business_type', ['halal', 'non-halal', 'mixed']);
            $table->integer('shariah_score');
            $table->decimal('profit_margin', 5, 2);
            $table->integer('years_in_business');
            $table->boolean('past_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
