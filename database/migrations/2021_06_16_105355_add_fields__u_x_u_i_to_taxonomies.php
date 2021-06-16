<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsUXUIToTaxonomies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('taxonomy_wheres', function (Blueprint $table) {
            $table->integer('stroke_with')->nullable()->unsigned();
            $table->integer('stroke_opacity')->nullable()->unsigned();
            $table->integer('line_dash')->nullable()->unsigned();
            $table->integer('min_visible_zoom')->nullable()->unsigned();
            $table->integer('min_sizes_zoom')->nullable()->unsigned();
            $table->integer('min_sizes')->nullable()->unsigned();
            $table->integer('max_sizes')->nullable()->unsigned();
            $table->integer('icon_zoom')->nullable()->unsigned();
            $table->integer('icon_sizes')->nullable()->unsigned();

        });
        Schema::table('taxonomy_whens', function (Blueprint $table) {
            $table->integer('stroke_with')->nullable()->unsigned();
            $table->integer('stroke_opacity')->nullable()->unsigned();
            $table->integer('line_dash')->nullable()->unsigned();
            $table->integer('min_visible_zoom')->nullable()->unsigned();
            $table->integer('min_sizes_zoom')->nullable()->unsigned();
            $table->integer('min_sizes')->nullable()->unsigned();
            $table->integer('max_sizes')->nullable()->unsigned();
            $table->integer('icon_zoom')->nullable()->unsigned();
            $table->integer('icon_sizes')->nullable()->unsigned();
        });
        Schema::table('taxonomy_themes', function (Blueprint $table) {
            $table->integer('stroke_with')->nullable()->unsigned();
            $table->integer('stroke_opacity')->nullable()->unsigned();
            $table->integer('line_dash')->nullable()->unsigned();
            $table->integer('min_visible_zoom')->nullable()->unsigned();
            $table->integer('min_sizes_zoom')->nullable()->unsigned();
            $table->integer('min_sizes')->nullable()->unsigned();
            $table->integer('max_sizes')->nullable()->unsigned();
            $table->integer('icon_zoom')->nullable()->unsigned();
            $table->integer('icon_sizes')->nullable()->unsigned();
        });
        Schema::table('taxonomy_activities', function (Blueprint $table) {
            $table->integer('stroke_with')->nullable()->unsigned();
            $table->integer('stroke_opacity')->nullable()->unsigned();
            $table->integer('line_dash')->nullable()->unsigned();
            $table->integer('min_visible_zoom')->nullable()->unsigned();
            $table->integer('min_sizes_zoom')->nullable()->unsigned();
            $table->integer('min_sizes')->nullable()->unsigned();
            $table->integer('max_sizes')->nullable()->unsigned();
            $table->integer('icon_zoom')->nullable()->unsigned();
            $table->integer('icon_sizes')->nullable()->unsigned();
        });
        Schema::table('taxonomy_poi_types', function (Blueprint $table) {
            $table->integer('stroke_with')->nullable()->unsigned();
            $table->integer('stroke_opacity')->nullable()->unsigned();
            $table->integer('line_dash')->nullable()->unsigned();
            $table->integer('min_visible_zoom')->nullable()->unsigned();
            $table->integer('min_sizes_zoom')->nullable()->unsigned();
            $table->integer('min_sizes')->nullable()->unsigned();
            $table->integer('max_sizes')->nullable()->unsigned();
            $table->integer('icon_zoom')->nullable()->unsigned();
            $table->integer('icon_sizes')->nullable()->unsigned();
        });
        Schema::table('taxonomy_targets', function (Blueprint $table) {
            $table->integer('stroke_with')->nullable()->unsigned();
            $table->integer('stroke_opacity')->nullable()->unsigned();
            $table->integer('line_dash')->nullable()->unsigned();
            $table->integer('min_visible_zoom')->nullable()->unsigned();
            $table->integer('min_sizes_zoom')->nullable()->unsigned();
            $table->integer('min_sizes')->nullable()->unsigned();
            $table->integer('max_sizes')->nullable()->unsigned();
            $table->integer('icon_zoom')->nullable()->unsigned();
            $table->integer('icon_sizes')->nullable()->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('taxonomies', function (Blueprint $table) {
            //
        });
    }
}
