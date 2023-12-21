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
        Schema::create('taxonomy_where_ugc_media', function (Blueprint $table) {
            $table->id();
            $table->integer('ugc_media_id')->unsigned();
            $table->integer('taxonomy_where_id')->unsigned();
            $table->foreign('ugc_media_id')
                ->references('id')
                ->on('ugc_media');
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
        Schema::dropIfExists('taxonomy_where_ugc_media');
    }
};
