<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableEcMediaEcPoi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ec_media_ec_poi', function (Blueprint $table) {
            
            $table->dropForeign(['ec_poi_id']);
            $table->dropForeign(['ec_media_id']);

            $table->foreign('ec_poi_id')
                ->references('id')
                ->on('ec_pois')
                ->onDelete('cascade');
            $table->foreign('ec_media_id')
                ->references('id')
                ->on('ec_media')
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
        Schema::table('ec_media_ec_poi', function (Blueprint $table) {
            
            $table->dropForeign(['ec_poi_id']);
            $table->dropForeign(['ec_media_id']);

            $table->foreign('ec_poi_id')
                ->references('id')
                ->on('ec_pois');
            $table->foreign('ec_media_id')
                ->references('id')
                ->on('ec_media');
        });
    }
}
