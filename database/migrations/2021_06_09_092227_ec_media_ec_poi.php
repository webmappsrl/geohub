<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EcMediaEcPoi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ec_media_ec_poi', function (Blueprint $table) {
            $table->id();
            $table->integer('ec_media_id')->unsigned();
            $table->integer('ec_poi_id')->unsigned();
            $table->foreign('ec_media_id')
                ->references('id')
                ->on('ec_media');
            $table->foreign('ec_poi_id')
                ->references('id')
                ->on('ec_pois');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ec_media_ec_poi');
    }
}
