<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UgcMediaTaxonomyWhere extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ugc_media_taxonomy_where', function (Blueprint $table) {
            $table->id();
            $table->integer('ugc_media_id')->unsigned();
            $table->integer('taxonomy_where_id')->unsigned();
        });

        Schema::table('ugc_media_taxonomy_where', function ($table) {
            $table->foreign('ugc_media_id')->references('id')->on('ugc_media');
            $table->foreign('taxonomy_where_id')->references('id')->on('taxonomy_wheres');
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
