<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            // APP section (config.json)
            $table->string('name');
            $table->string('app_id')->unique();
            $table->string('customerName');

            // MAP section
            $table->integer('maxZoom')->default(16);
            $table->integer('minZoom')->default(12);
            $table->integer('defZoom')->default(12);

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign("user_id")
                ->references("id")
                ->on("users");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('apps');
    }
}
