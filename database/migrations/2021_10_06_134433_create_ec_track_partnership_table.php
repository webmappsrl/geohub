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
        Schema::create('ec_track_partnership', function (Blueprint $table) {
            $table->integer('ec_track_id')->unsigned();
            $table->integer('partnership_id')->unsigned();
            $table->foreign('ec_track_id')
                ->references('id')
                ->on('ec_tracks');
            $table->foreign('partnership_id')
                ->references('id')
                ->on('partnerships');

            $table->unique('partnership_id', 'ec_track_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ec_track_partnership');
    }
};
