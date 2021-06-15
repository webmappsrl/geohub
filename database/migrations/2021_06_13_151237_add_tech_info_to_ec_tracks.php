<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTechInfoToEcTracks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ec_tracks', function (Blueprint $table) {
            // Km
            $table->float('distance')->nullable();
            // meter
            $table->float('ascent')->nullable();
            // meter
            $table->float('descent')->nullable();
            // meter
            $table->float('ele_from')->nullable();
            // meter
            $table->float('ele_to')->nullable();
            // meter
            $table->float('ele_min')->nullable();
            // meter
            $table->float('ele_max')->nullable();
            // minutes
            $table->float('duration_forward')->nullable();
            // minutes
            $table->float('duration_backward')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ec_tracks', function (Blueprint $table) {
            $table->dropColumn('distance');
            $table->dropColumn('ascent');
            $table->dropColumn('descent');
            $table->dropColumn('ele_from');
            $table->dropColumn('ele_to');
            $table->dropColumn('ele_min');
            $table->dropColumn('ele_max');
            $table->dropColumn('duration_forward');
            $table->dropColumn('duration_backward');

        });
    }
}
