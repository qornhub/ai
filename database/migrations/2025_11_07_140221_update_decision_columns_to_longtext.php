<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('decisions', function (Blueprint $table) {
            $table->longText('ai_decision')->change();
            $table->longText('ai_explanation')->change();
        });
    }

    public function down()
    {
        Schema::table('decisions', function (Blueprint $table) {
            $table->string('ai_decision')->change();
            $table->string('ai_explanation')->change();
        });
    }
};
