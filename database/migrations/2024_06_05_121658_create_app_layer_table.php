<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppLayerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_layer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layer_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('layerable_id');
            $table->string('layerable_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_layer');
    }
}
