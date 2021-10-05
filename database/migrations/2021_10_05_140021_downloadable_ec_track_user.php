<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DownloadableEcTrackUser extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('downloadable_ec_track_user', function (Blueprint $table) {
            $table->id();
            $table->integer('ec_track_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->foreign('ec_track_id')
                ->references('id')
                ->on('ec_tracks');
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('downloadable_ec_track_user');
    }
}
