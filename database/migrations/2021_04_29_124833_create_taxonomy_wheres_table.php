<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaxonomyWheresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // TODO: approfondire postgist https://github.com/mstaack/laravel-postgis
        Schema::create('taxonomy_wheres', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('name')->nullable();
            $table->multiPolygon('geometry')->nullable();
            // ImportAndSync* Class name used to import data. When is null it means that is not imported and can be
            // edited by user interface.
            $table->string('import_method')->nullable();
            $table->string('source_id')->nullable();
            $table->integer('admin_level')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('taxonomy_wheres');
    }
}
