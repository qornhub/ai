<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('applicants', function (Blueprint $table) {
            // Only add if it does NOT already exist
            if (!Schema::hasColumn('applicants', 'user_id')) {
                $table->unsignedBigInteger('user_id')->after('id');
            }
        });
    }

    public function down()
    {
        Schema::table('applicants', function (Blueprint $table) {
            // Only drop if it exists
            if (Schema::hasColumn('applicants', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
