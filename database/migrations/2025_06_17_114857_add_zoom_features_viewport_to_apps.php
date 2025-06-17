<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZoomFeaturesViewportToApps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // TODO: ADD2WMPACKAGE
        Schema::table('apps', function (Blueprint $table) {
            $table->integer('min_zoom_features_in_viewport')->default(10);
            $table->integer('max_zoom_features_in_viewport')->default(12);
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
            $table->dropColumn('min_zoom_features_in_viewport');
            $table->dropColumn('max_zoom_features_in_viewport');
        });
    }
}
