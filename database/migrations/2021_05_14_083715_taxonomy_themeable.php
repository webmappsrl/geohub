<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TaxonomyThemeable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxonomy_themeables', function (Blueprint $table) {
            $table->integer('taxonomy_themeable_id')->unsigned();
            $table->integer('taxonomy_theme_id')->unsigned();
            $table->string('taxonomy_themeable_type');
            $table->foreign('taxonomy_theme_id')
                ->references('id')
                ->on('taxonomy_themes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('taxonomy_themeables');
    }
}
