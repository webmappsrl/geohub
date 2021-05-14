<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TaxonomyWhereable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxonomy_whereables', function (Blueprint $table) {
            $table->integer('taxonomy_whereable_id')->unsigned();
            $table->integer('taxonomy_where_id')->unsigned();
            $table->string('taxonomy_whereable_type');
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
        Schema::dropIfExists('taxonomy_whereables');
    }
}
