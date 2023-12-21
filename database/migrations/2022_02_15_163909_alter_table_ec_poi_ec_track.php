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
        Schema::table('ec_poi_ec_track', function (Blueprint $table) {

            $table->dropForeign(['ec_poi_id']);
            $table->dropForeign(['ec_track_id']);

            $table->foreign('ec_poi_id')
                ->references('id')
                ->on('ec_pois')
                ->onDelete('cascade');
            $table->foreign('ec_track_id')
                ->references('id')
                ->on('ec_tracks')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ec_poi_ec_track', function (Blueprint $table) {

            $table->dropForeign(['ec_poi_id']);
            $table->dropForeign(['ec_track_id']);

            $table->foreign('ec_poi_id')
                ->references('id')
                ->on('ec_pois');
            $table->foreign('ec_track_id')
                ->references('id')
                ->on('ec_tracks');
        });
    }
};
