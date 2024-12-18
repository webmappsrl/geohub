<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnDeleteCascadeOnUgcsPivotTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ugc_media_ugc_track', function (Blueprint $table) {
            $table->dropForeign(['ugc_media_id']);
            $table->foreign('ugc_media_id')->references('id')->on('ugc_media')->onDelete('cascade');
        });

        Schema::table('ugc_media_ugc_poi', function (Blueprint $table) {
            $table->dropForeign(['ugc_media_id']);
            $table->foreign('ugc_media_id')->references('id')->on('ugc_media')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ugc_media_ugc_track', function (Blueprint $table) {
            $table->dropForeign(['ugc_media_id']);
            $table->foreign('ugc_media_id')->references('id')->on('ugc_media');
        });

        Schema::table('ugc_media_ugc_poi', function (Blueprint $table) {
            $table->dropForeign(['ugc_media_id']);
            $table->foreign('ugc_media_id')->references('id')->on('ugc_media');
        });
    }
}
