<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EcPoiEcTrack extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ec_poi_ec_track', function (Blueprint $table) {
            $table->id();
            $table->integer('ec_poi_id')->unsigned();
            $table->integer('ec_track_id')->unsigned();
            $table->foreign('ec_poi_id')
                ->references('id')
                ->on('ec_pois');
            $table->foreign('ec_track_id')
                ->references('id')
                ->on('ec_tracks');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
