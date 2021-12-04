<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutSourceFeaturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // table: out_source_features (type, geometry, tags, provider, source_id)
        Schema::create('out_source_features', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('type');
            $table->geometry('geometry')->nullable();
            $table->json('tags')->nullable();
            $table->string('source_id');
            $table->string('provider');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('out_source_features');
    }
}
