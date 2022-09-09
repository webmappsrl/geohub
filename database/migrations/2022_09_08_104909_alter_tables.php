<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ugc_media', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
        Schema::table('ugc_pois', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
        Schema::table('ugc_tracks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
        Schema::table('taxonomy_where_ugc_media', function (Blueprint $table) {
            $table->dropForeign(['ugc_media_id']);
            $table->foreign('ugc_media_id')
                ->references('id')
                ->on('ugc_media')
                ->onDelete('cascade');
        });
        Schema::table('ugc_media_ugc_poi', function (Blueprint $table) {
            $table->dropForeign(['ugc_media_id']);
            $table->dropForeign(['ugc_poi_id']);
            $table->foreign('ugc_media_id')
                ->references('id')
                ->on('ugc_media')
                ->onDelete('cascade');
            $table->foreign('ugc_poi_id')
                ->references('id')
                ->on('ugc_pois')
                ->onDelete('cascade');
        });
        Schema::table('taxonomy_where_ugc_poi', function (Blueprint $table) {
            $table->dropForeign(['ugc_poi_id']);
            $table->foreign('ugc_poi_id')
                ->references('id')
                ->on('ugc_pois')
                ->onDelete('cascade');
        });
        Schema::table('taxonomy_where_ugc_track', function (Blueprint $table) {
            $table->dropForeign(['ugc_track_id']);
            $table->foreign('ugc_track_id')
                ->references('id')
                ->on('ugc_tracks')
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
        Schema::table('ugc_media', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
        Schema::table('ugc_media', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
        Schema::table('ugc_pois', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
        Schema::table('ugc_tracks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
        Schema::table('taxonomy_where_ugc_media', function (Blueprint $table) {
            $table->dropForeign(['ugc_media_id']);
            $table->foreign('ugc_media_id')
                ->references('id')
                ->on('ugc_media');
        });
        Schema::table('ugc_media_ugc_poi', function (Blueprint $table) {
            $table->dropForeign(['ugc_media_id']);
            $table->dropForeign(['ugc_poi_id']);
            $table->foreign('ugc_media_id')
                ->references('id')
                ->on('ugc_media');
            $table->foreign('ugc_poi_id')
                ->references('id')
                ->on('ugc_pois');
        });
        Schema::table('taxonomy_where_ugc_poi', function (Blueprint $table) {
            $table->dropForeign(['ugc_poi_id']);
            $table->foreign('ugc_poi_id')
                ->references('id')
                ->on('ugc_pois');
        });
        Schema::table('taxonomy_where_ugc_track', function (Blueprint $table) {
            $table->dropForeign(['ugc_track_id']);
            $table->foreign('ugc_track_id')
                ->references('id')
                ->on('ugc_tracks');
        });
    }
}
