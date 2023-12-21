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
        Schema::create('ec_media_ec_track', function (Blueprint $table) {
            $table->id();
            $table->integer('ec_media_id')->unsigned();
            $table->integer('ec_track_id')->unsigned();
            $table->foreign('ec_media_id')
                ->references('id')
                ->on('ec_media');
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
        Schema::dropIfExists('ec_media_ec_track');
    }
};
