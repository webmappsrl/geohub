<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('out_source_features', function (Blueprint $table) {
            $table->jsonb('raw_data')->nullable();
            $table->string('endpoint')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('out_source_features', function (Blueprint $table) {
            $table->dropColumn('raw_data');
            $table->dropColumn('endpoint');
        });
    }
};
