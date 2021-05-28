<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TaxonomyTargetable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxonomy_targetables', function (Blueprint $table) {
            $table->integer('taxonomy_targetable_id')->unsigned();
            $table->integer('taxonomy_target_id')->unsigned();
            $table->string('taxonomy_targetable_type');
            $table->foreign('taxonomy_target_id')
                ->references('id')
                ->on('taxonomy_targets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('taxonomy_targetables');
    }
}
