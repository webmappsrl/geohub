<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TaxonomyPoiTypeable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxonomy_poi_typeables', function (Blueprint $table) {
            $table->integer('taxonomy_poi_typeable_id')->unsigned();
            $table->integer('taxonomy_poi_type_id')->unsigned();
            $table->string('taxonomy_poi_typeable_type');
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
        Schema::dropIfExists('taxonomy_poi_typeables');
    }
}
