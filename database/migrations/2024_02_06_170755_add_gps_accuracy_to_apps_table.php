<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGpsAccuracyToAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->integer('gps_accuracy_default')->default('10')->nullable();
            $table->json('classification')->default(json_encode([]))->nullable();
            $table->boolean('classification_show')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn('gps_accuracy_default');
            $table->dropColumn('classification');
            $table->dropColumn('classification_show');
        });
    }
}
