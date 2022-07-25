<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptionsStartEndIconsAndRefTrackToApps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->boolean('start_end_icons_show')->default(false);
            $table->integer('start_end_icons_min_zoom')->default(10);
            $table->boolean('ref_on_track_show')->default(false);
            $table->integer('ref_on_track_min_zoom')->default(10);
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
            $table->dropColumn('start_end_icons_show');
            $table->dropColumn('start_end_icons_min_zoom');
            $table->dropColumn('ref_on_track_show');
            $table->dropColumn('ref_on_track_min_zoom');

        });
    }
}
