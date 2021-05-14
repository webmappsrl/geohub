<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EcMediaTaxonomyPoiTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ec_media_taxonomy_poi_type', function (Blueprint $table) {
            $table->id();
            $table->integer('ec_media_id')->unsigned();
            $table->integer('taxonomy_poi_type_id')->unsigned();
            $table->foreign('ec_media_id')
                ->references('id')
                ->on('ec_media');
            $table->foreign('taxonomy_poi_type_id')
                ->references('id')
                ->on('taxonomy_poi_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ec_media_taxonomy_poi_type');
    }
}
