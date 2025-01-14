<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TaxonomyWhereUgcPoi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxonomy_where_ugc_poi', function (Blueprint $table) {
            $table->id();
            $table->integer('ugc_poi_id')->unsigned();
            $table->integer('taxonomy_where_id')->unsigned();
            $table->foreign('ugc_poi_id')
                ->references('id')
                ->on('ugc_pois');
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
        Schema::dropIfExists('taxonomy_where_ugc_poi');
    }
}
