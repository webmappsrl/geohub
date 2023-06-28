<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EcMediaLayer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ec_media_layer', function (Blueprint $table) {
            $table->id();
            $table->integer('ec_media_id')->unsigned();
            $table->integer('layer_id')->unsigned();
            $table->foreign('ec_media_id')
                ->references('id')
                ->on('ec_media')
                ->onDelete('cascade');
            $table->foreign('layer_id')
                ->references('id')
                ->on('layers')
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
        Schema::table('ec_media_layer', function (Blueprint $table) {
            $table->dropIfExists('ec_media_layer');
        });
    }
}
