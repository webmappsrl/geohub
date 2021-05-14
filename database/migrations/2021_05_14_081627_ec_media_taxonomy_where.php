<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EcMediaTaxonomyWhere extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ec_media_taxonomy_where', function (Blueprint $table) {
            $table->id();
            $table->integer('ec_media_id')->unsigned();
            $table->integer('taxonomy_where_id')->unsigned();
            $table->foreign('ec_media_id')
                ->references('id')
                ->on('ec_media');
            $table->foreign('taxonomy_where_id')
                ->references('id')
                ->on('taxonomy_wheres');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ec_media_taxonomy_where');
    }
}
