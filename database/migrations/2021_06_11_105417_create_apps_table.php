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
