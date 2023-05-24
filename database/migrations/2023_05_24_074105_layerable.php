<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Layerable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('layerables', function (Blueprint $table) {

            $table->integer('layerable_id')->unsigned();
            $table->integer('layer_id')->unsigned();
            $table->string('layerable_type');
            $table->foreign('layer_id')
                ->references('id')
                ->on('layers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table->dropIfExists('layerables');
    }
}
