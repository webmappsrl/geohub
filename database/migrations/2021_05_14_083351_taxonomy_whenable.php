<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxonomy_whenables', function (Blueprint $table) {
            $table->integer('taxonomy_whenable_id')->unsigned();
            $table->integer('taxonomy_when_id')->unsigned();
            $table->string('taxonomy_whenable_type');
            $table->foreign('taxonomy_when_id')
                ->references('id')
                ->on('taxonomy_whens');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('taxonomy_whenables');
    }
};
