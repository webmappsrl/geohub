<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOverlayLayerIdToLayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('layers', function (Blueprint $table) {
            $table->unsignedBigInteger('overlay_layer_id')->nullable();
            $table->foreign('overlay_layer_id')->references('id')->on('overlay_layers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('layers', function (Blueprint $table) {
            $table->dropForeign(['overlay_layer_id']);
            $table->dropColumn('overlay_layer_id');
        });
    }
}
