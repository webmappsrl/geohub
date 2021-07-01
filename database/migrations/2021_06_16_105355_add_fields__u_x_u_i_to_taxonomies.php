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
        $taxonomies = ['taxonomy_wheres', 'taxonomy_whens', 'taxonomy_themes', 'taxonomy_activities', 'taxonomy_targets', 'taxonomy_poi_types'];

        foreach ($taxonomies as $taxonomy) {
            Schema::table($taxonomy, function (Blueprint $table) {
                $table->float('stroke_width')->nullable()->unsigned();
                $table->float('stroke_opacity')->nullable()->unsigned();
                $table->text('line_dash')->nullable()->unsigned();
                $table->float('min_visible_zoom')->nullable()->unsigned();
                $table->float('min_size_zoom')->nullable()->unsigned();
                $table->float('min_size')->nullable()->unsigned();
                $table->float('max_size')->nullable()->unsigned();
                $table->float('icon_zoom')->nullable()->unsigned();
                $table->float('icon_size')->nullable()->unsigned();
            });
        }

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
