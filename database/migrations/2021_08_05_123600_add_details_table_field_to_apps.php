<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsTableFieldToApps extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('apps', function (Blueprint $table) {
            $table->boolean('table_details_show_duration_forward')->default(true);
            $table->boolean('table_details_show_duration_backward')->default(false);
            $table->boolean('table_details_show_distance')->default(true);
            $table->boolean('table_details_show_ascent')->default(true);
            $table->boolean('table_details_show_descent')->default(true);
            $table->boolean('table_details_show_ele_max')->default(true);
            $table->boolean('table_details_show_ele_min')->default(true);
            $table->boolean('table_details_show_ele_from')->default(false);
            $table->boolean('table_details_show_ele_to')->default(false);
            $table->boolean('table_details_show_scale')->default(true);
            $table->boolean('table_details_show_cai_scale')->default(false);
            $table->boolean('table_details_show_mtb_scale')->default(false);
            $table->boolean('table_details_show_ref')->default(true);
            $table->boolean('table_details_show_surface')->default(false);
            $table->boolean('table_details_show_geojson_download')->default(false);
            $table->boolean('table_details_show_shapefile_download')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn('table_details_show_duration_forward');
            $table->dropColumn('table_details_show_duration_backward');
            $table->dropColumn('table_details_show_distance');
            $table->dropColumn('table_details_show_ascent');
            $table->dropColumn('table_details_show_descent');
            $table->dropColumn('table_details_show_ele_max');
            $table->dropColumn('table_details_show_ele_min');
            $table->dropColumn('table_details_show_ele_from');
            $table->dropColumn('table_details_show_ele_to');
            $table->dropColumn('table_details_show_scale');
            $table->dropColumn('table_details_show_cai_scale');
            $table->dropColumn('table_details_show_mtb_scale');
            $table->dropColumn('table_details_show_ref');
            $table->dropColumn('table_details_show_surface');
            $table->dropColumn('table_details_show_geojson_download');
            $table->dropColumn('table_details_show_shapefile_download');
        });
    }
}
