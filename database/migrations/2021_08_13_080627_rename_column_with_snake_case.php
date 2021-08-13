<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameColumnWithSnakeCase extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('apps', function (Blueprint $table) {
            $table->renameColumn('"customerName"', 'customer_name');
            $table->renameColumn('"maxZoom"', 'map_max_zoom');
            $table->renameColumn('"minZoom"', 'map_min_zoom');
            $table->renameColumn('"defZoom"', 'map_def_zoom');
            $table->renameColumn('"fontFamilyHeader"', 'font_family_header');
            $table->renameColumn('"fontFamilyContent"', 'font_family_content');
            $table->renameColumn('"defaultFeatureColor"', 'default_feature_color');
            $table->renameColumn('"primary"', 'primary_color');
            $table->renameColumn('"startUrl"', 'start_url');
            $table->renameColumn('"showEditLink"', 'show_edit_link');
            $table->renameColumn('"skipRouteIndexDownload"', 'skip_route_index_download');
            $table->renameColumn('"poiMinRadius"', 'poi_min_radius');
            $table->renameColumn('"poiMaxRadius"', 'poi_max_radius');
            $table->renameColumn('"poiIconZoom"', 'poi_icon_zoom');
            $table->renameColumn('"poiIconRadius"', 'poi_icon_radius');
            $table->renameColumn('"poiMinZoom"', 'poi_min_zoom');
            $table->renameColumn('"poiLabelMinZoom"', 'poi_label_min_zoom');
            $table->renameColumn('"showTrackRefLabel"', 'show_track_ref_label');
            $table->renameColumn('"showGpxDownload"', 'table_details_show_gpx_download');
            $table->renameColumn('"showKmlDownload"', 'table_details_show_kml_download');
            $table->renameColumn('"showRelatedPoi"', 'table_details_show_related_poi');
            $table->renameColumn('"enableRouting"', 'enable_routing');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('apps', function (Blueprint $table) {
            $table->renameColumn('customer_name', '"customerName"');
            $table->renameColumn('map_max_zoom', '"maxZoom"');
            $table->renameColumn('map_min_zoom', '"minZoom"');
            $table->renameColumn('map_def_zoom', '"defZoom"');
            $table->renameColumn('font_family_header', '"fontFamilyHeader"');
            $table->renameColumn('font_family_content', '"fontFamilyContent"');
            $table->renameColumn('default_feature_color', '"defaultFeatureColor"');
            $table->renameColumn('primary_color', '"primary"');
            $table->renameColumn('start_url', '"startUrl"');
            $table->renameColumn('show_edit_link', '"showEditLink"');
            $table->renameColumn('skip_route_index_download', '"skipRouteIndexDownload"');
            $table->renameColumn('poi_min_radius', '"poiMinRadius"');
            $table->renameColumn('poi_max_radius', '"poiMaxRadius"');
            $table->renameColumn('poi_icon_zoom', '"poiIconZoom"');
            $table->renameColumn('poi_icon_radius', '"poiIconRadius"');
            $table->renameColumn('poi_min_zoom', '"poiMinZoom"');
            $table->renameColumn('poi_label_min_zoom', '"poiLabelMinZoom"');
            $table->renameColumn('show_track_ref_label', '"showTrackRefLabel"');
            $table->renameColumn('table_details_show_gpx_download', '"showGpxDownload"');
            $table->renameColumn('table_details_show_kml_download', '"showKmlDownload"');
            $table->renameColumn('table_details_show_related_poi', '"showRelatedPoi"');
            $table->renameColumn('enable_routing', '"enableRouting"');
        });
    }
}
