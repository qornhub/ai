<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up()
{
    Schema::create('decisions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
        $table->string('ai_decision');
        $table->longText('ai_explanation');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};
