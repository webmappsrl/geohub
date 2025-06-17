<?php

namespace App\Traits;

use App\Enums\AppTiles;
use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcTrack;
use App\Models\OverlayLayer;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait ConfTrait
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id  the app id in the database
     * @return JsonResponse
     */
    public function config()
    {

        $data = [];

        $data = array_merge($data, $this->config_section_app());
        $data = array_merge($data, $this->config_section_webapp());
        $data = array_merge($data, $this->config_section_translations());
        $data = array_merge($data, $this->config_section_home());
        $data = array_merge($data, $this->config_section_languages());
        $data = array_merge($data, $this->config_section_map());
        $data = array_merge($data, $this->config_section_pages());
        $data = array_merge($data, $this->config_section_theme());
        $data = array_merge($data, $this->config_section_options());
        $data = array_merge($data, $this->config_section_tables());
        $data = array_merge($data, $this->config_section_routing());
        $data = array_merge($data, $this->config_section_report());
        $data = array_merge($data, $this->config_section_geolocation());
        $data = array_merge($data, $this->config_section_auth());
        $data = array_merge($data, $this->config_section_offline());

        return $data;
    }

    private function config_section_app(): array
    {
        $data = [];

        $data['APP']['name'] = $this->name;
        $data['APP']['sku'] = $this->sku;
        $data['APP']['customerName'] = $this->customer_name;
        $data['APP']['geohubId'] = $this->id;

        if (! is_null($this->welcome)) {
            $data['APP']['welcome'] = [];
            $welcome = $this->toArray()['welcome'];
            $data['APP']['welcome'] = $welcome;
        }
        if ($this->android_store_link) {
            $data['APP']['androidStore'] = $this->android_store_link;
        }

        if ($this->ios_store_link) {
            $data['APP']['iosStore'] = $this->ios_store_link;
        }

        if ($this->social_track_text) {
            $data['APP']['socialTrackText'] = $this->social_track_text;
        }
        if ($this->social_share_text) {
            $data['APP']['socialShareText'] = $this->getTranslations('social_share_text');
        }
        if ($this->poi_acquisition_form) {
            $data['APP']['poi_acquisition_form'] = json_decode($this->poi_acquisition_form, true);
        }
        if ($this->track_acquisition_form) {
            $data['APP']['track_acquisition_form'] = json_decode($this->track_acquisition_form, true);
        }

        return $data;
    }

    private function config_section_webapp(): array
    {
        $data = [];

        $data['WEBAPP']['draw_track_show'] = $this->draw_track_show;
        $data['WEBAPP']['draw_poi_show'] = $this->draw_poi_show; // TODO: ADD2WMPACKAGE
        $data['WEBAPP']['editing_inline_show'] = $this->editing_inline_show;
        $data['WEBAPP']['splash_screen_show'] = $this->splash_screen_show;
        if ($this->gu_id) {
            $data['WEBAPP']['gu_id'] = $this->gu_id;
        }
        if ($this->embed_code_body) {
            $data['WEBAPP']['embed_code_body'] = $this->embed_code_body;
        }

        return $data;
    }

    private function config_section_translations(): array
    {
        $data = [];
        $data['TRANSLATIONS'] = [];
        if (! is_null($this->translations_it)) {
            $data['TRANSLATIONS']['it'] = json_decode($this->translations_it, true);
        }
        if (! is_null($this->translations_en)) {
            $data['TRANSLATIONS']['en'] = json_decode($this->translations_en, true);
        }

        return $data;
    }

    private function config_section_home(): array
    {
        $data = [];

        $data['HOME'][] = [
            'view' => 'title',
            'title' => $this->name,
        ];

        if (! empty($this->config_home)) {
            $data = json_decode($this->config_home, true);
        } elseif ($this->layers->count() > 0) {
            foreach ($this->layers()->orderBy('rank')->get() as $layer) {
                $data['HOME'][] = [
                    'view' => 'compact-horizontal',
                    'title' => $layer->title,
                    'terms' => [$layer->id],
                ];
            }
        }

        return $data;
    }

    private function config_section_languages(): array
    {
        $data['LANGUAGES']['default'] = $this->default_language;
        if (isset($this->available_languages)) {
            $data['LANGUAGES']['available'] = json_decode($this->available_languages, true);
        }

        return $data;
    }

    public function get_unique_taxonomies($taxonomy_relation)
    {
        $all_taxonomies = collect();

        // Tassonomie dai layer
        $all_taxonomies = $this->collect_taxonomies_from_layers($all_taxonomies, $taxonomy_relation);

        // Tassonomie dalle tracce
        $all_taxonomies = $this->collect_taxonomies_from_tracks($all_taxonomies, $taxonomy_relation);

        $unique_taxonomies = $all_taxonomies->unique('id')->values()->all();

        return $unique_taxonomies;
    }

    private function collect_taxonomies_from_layers(Collection $all_taxonomies, $taxonomy_relation)
    {
        foreach ($this->layers as $layer) {
            if (! method_exists($layer, $taxonomy_relation)) {
                throw new Exception("The taxonomy relation {$taxonomy_relation} does not exist on the Layer model.");
            }

            $taxonomies = $layer->$taxonomy_relation->map(function ($taxonomy) {
                return $this->filter_taxonomy($taxonomy);
            });

            $all_taxonomies = $all_taxonomies->merge($taxonomies);
        }

        return $all_taxonomies;
    }

    private function collect_taxonomies_from_tracks(Collection $all_taxonomies, $taxonomy_relation)
    {
        $ec_tracks = EcTrack::where('user_id', $this->user_id)->get();

        foreach ($ec_tracks as $track) {
            if (! method_exists($track, $taxonomy_relation)) {
                throw new Exception("The taxonomy relation {$taxonomy_relation} does not exist on the EcTrack model.");
            }

            $track_taxonomies = $track->$taxonomy_relation->map(function ($taxonomy) {
                return $this->filter_taxonomy($taxonomy);
            });

            $all_taxonomies = $all_taxonomies->merge($track_taxonomies);
        }

        return $all_taxonomies;
    }

    private function filter_taxonomy($taxonomy)
    {
        return array_filter([
            'id' => $taxonomy->id,
            'identifier' => $taxonomy->identifier,
            'name' => $taxonomy->getTranslations('name'),
            'color' => $taxonomy->color ?? null,
        ], function ($value) {
            return ! is_null($value);
        });
    }

    private function config_section_map(): array
    {
        $data = [];
        // MAP section (zoom)
        $data['MAP']['defZoom'] = $this->map_def_zoom;
        $data['MAP']['maxZoom'] = $this->map_max_zoom;
        $data['MAP']['minZoom'] = $this->map_min_zoom;
        $data['MAP']['maxStrokeWidth'] = $this->map_max_stroke_width;
        $data['MAP']['minStrokeWidth'] = $this->map_min_stroke_width;
        $data['MAP']['tiles'] = array_map(function ($v) {
            return json_decode($v);
        }, json_decode($this->tiles, true));

        if (is_null($this->map_bbox)) {
            $data['MAP']['bbox'] = $this->_getBBox();
        } else {
            $data['MAP']['bbox'] = json_decode($this->map_bbox, true);
        }

        // MAP section (bbox)
        if (in_array($this->api, ['elbrus'])) {
            $data['MAP']['bbox'] = $this->_getBBox();
            // Map section layers
            $data['MAP']['layers'][0]['label'] = 'Mappa';
            $data['MAP']['layers'][0]['type'] = 'maptile';
            $data['MAP']['layers'][0]['tilesUrl'] = 'https://api.webmapp.it/tiles/';
            try {
                $data['MAP']['overlays'] = json_decode($this->external_overlays);
            } catch (\Exception $e) {
                Log::warning('The overlays in the app '.$this->id.' are not correctly mapped. Error: '.$e->getMessage());
            }
        }

        if ($this->layers->count() > 0) {
            $layers = [];
            foreach ($this->layers as $layer) {
                $item = $layer->toArray();
                try {

                    if (isset($item['bbox'])) {
                        $item['bbox'] = array_map('floatval', json_decode(strval($item['bbox']), true));
                    }
                } catch (\Exception  $e) {
                    Log::warning('The bbox value '.$layer->id.' are not correct. Error: '.$e->getMessage());
                }
                // style
                foreach (['color', 'fill_color', 'fill_opacity', 'stroke_width', 'stroke_opacity', 'zindex', 'line_dash'] as $field) {
                    $item['style'][$field] = $item[$field];
                    unset($item[$field]);
                }
                // behaviour
                foreach (['noDetails', 'noInteraction', 'minZoom', 'maxZoom', 'preventFilter', 'invertPolygons', 'alert', 'show_label'] as $field) {
                    $item['behaviour'][$field] = $item[$field];
                    unset($item[$field]);
                }
                unset($item['created_at']);
                unset($item['updated_at']);
                unset($item['sku']);
                unset($item['generate_edges']);

                // FEATURE IMAGE:
                $feature_image = null;
                if (! empty($layer->featureImage) && $layer->featureImage->count() > 0) {
                    $feature_image = $layer->featureImage->thumbnail('400x200');
                    if (! is_null($layer->featureImage->thumbnail('400x200'))) {
                        $item['feature_image'] = $layer->featureImage->thumbnail('400x200');
                    }
                } else {
                    if ($layer->taxonomyWheres->count() > 0) {
                        foreach ($layer->taxonomyWheres as $term) {
                            if (isset($term->feature_image) && ! empty($term->feature_image)) {
                                $feature_image = $term->feature_image;
                            }
                        }
                    }
                    if ($feature_image == null && $layer->taxonomyThemes->count() > 0) {
                        foreach ($layer->taxonomyThemes as $term) {
                            if (isset($term->feature_image) && ! empty($term->feature_image)) {
                                $feature_image = $term->feature_image;
                            }
                        }
                    }

                    if ($feature_image == null && $layer->taxonomyActivities->count() > 0) {
                        foreach ($layer->taxonomyActivities as $term) {
                            if (isset($term->feature_image) && ! empty($term->feature_image)) {
                                $feature_image = $term->feature_image;
                            }
                        }
                    }

                    if ($feature_image == null && $layer->taxonomyWhens->count() > 0) {
                        foreach ($layer->taxonomyWhens as $term) {
                            if (isset($term->feature_image) && ! empty($term->feature_image)) {
                                $feature_image = $term->feature_image;
                            }
                        }
                    }

                    if ($feature_image == null && $layer->taxonomyTargets->count() > 0) {
                        foreach ($layer->taxonomyTargets as $term) {
                            if (isset($term->feature_image) && ! empty($term->feature_image)) {
                                $feature_image = $term->feature_image;
                            }
                        }
                    }

                    if ($feature_image == null && $layer->taxonomyPoiTypes->count() > 0) {
                        foreach ($layer->taxonomyPoiTypes as $term) {
                            if (isset($term->feature_image) && ! empty($term->feature_image)) {
                                $feature_image = $term->feature_image;
                            }
                        }
                    }

                    if ($feature_image != null) {
                        // Retrieve proper image
                        $image = EcMedia::find($feature_image);
                        if (! is_null($image->thumbnail('400x200'))) {
                            $item['feature_image'] = $image->thumbnail('400x200');
                        }
                    }
                }

                // remove useless attribute geometry from taxonomy where of layer
                if ($item['taxonomy_wheres']) {
                    $unsetAttr = ['geometry', 'query_string'];
                    for ($i = 0; $i < count($item['taxonomy_wheres']); $i++) {
                        foreach ($unsetAttr as $attr) {
                            unset($item['taxonomy_wheres'][$i][$attr]);
                        }
                    }
                }

                if ($layer->generate_edges || $this->generate_layers_edges) {
                    $item['edges'] = $layer->generateLayerEdges();
                }

                $layers[] = $item;
            }

            $rank = array_column($layers, 'rank');
            array_multisort($rank, SORT_ASC, $layers);
            $data['MAP']['layers'] = $layers;
        }

        // POIS section
        $data['MAP']['pois']['apppoisApiLayer'] = $this->app_pois_api_layer;
        $data['MAP']['pois']['skipRouteIndexDownload'] = $this->skip_route_index_download;
        $data['MAP']['pois']['poiMinRadius'] = $this->poi_min_radius;
        $data['MAP']['pois']['poiMaxRadius'] = $this->poi_max_radius;
        $data['MAP']['pois']['poiIconZoom'] = $this->poi_icon_zoom;
        $data['MAP']['pois']['poiIconRadius'] = $this->poi_icon_radius;
        $data['MAP']['pois']['poiMinZoom'] = $this->poi_min_zoom;
        $data['MAP']['pois']['poiLabelMinZoom'] = $this->poi_label_min_zoom;
        $data['MAP']['pois']['taxonomies'] = $this->getAllPoiTaxonomies();
        $data['MAP']['pois']['poi_interaction'] = $this->poi_interaction;

        // Other Options
        $data['MAP']['start_end_icons_show'] = $this->start_end_icons_show;
        $data['MAP']['start_end_icons_min_zoom'] = $this->start_end_icons_min_zoom;
        $data['MAP']['ref_on_track_show'] = $this->ref_on_track_show;
        $data['MAP']['ref_on_track_min_zoom'] = $this->ref_on_track_min_zoom;
        $data['MAP']['record_track_show'] = $this->geolocation_record_enable;
        $data['MAP']['alert_poi_show'] = $this->alert_poi_show;
        $data['MAP']['alert_poi_radius'] = $this->alert_poi_radius;
        $data['MAP']['flow_line_quote_show'] = $this->flow_line_quote_show;
        $data['MAP']['flow_line_quote_orange'] = $this->flow_line_quote_orange;
        $data['MAP']['flow_line_quote_red'] = $this->flow_line_quote_red;
        $data['MAP']['show_track_direction_arrow'] = $this->show_track_direction_arrow; // TODO: ADD2WMPACKAGE

        // Tiles
        if ($this->tiles && ! empty(json_decode($this->tiles, true))) {
            $appTiles = new AppTiles;
            $data['MAP']['controls']['tiles'][] = ['label' => $this->getTranslations('tiles_label'), 'type' => 'title'];
            $ta = array_map(function ($v) use ($appTiles) {
                $v = json_decode($v, true);
                $tile = $appTiles->getConstant(key($v));
                $tile['type'] = 'button';

                return $tile;
            }, json_decode($this->tiles, true));
            array_push($data['MAP']['controls']['tiles'], ...$ta);
        }

        // Overlays
        if ($this->overlayLayers->count() > 0) {
            $data['MAP']['controls']['overlays'][] = ['label' => $this->getTranslations('overlays_label'), 'type' => 'title'];
            $overlays = array_map(function ($overlay) {
                $array = [];
                $overlay = OverlayLayer::find($overlay['id']);
                $array['label'] = $overlay->getTranslations('label');
                if ($overlay['default']) {
                    $array['default'] = $overlay['default'];
                }
                if (isset($overlay['icon'])) {
                    $array['icon'] = $overlay['icon'];
                }
                if (isset($overlay['fill_color'])) {
                    $array['fillColor'] = $this->hexToRgba($overlay['fill_color']);
                } else {
                    $array['fillColor'] = $this->hexToRgba($overlay->app->primary_color);
                }
                if (isset($overlay['stroke_color'])) {
                    $array['strokeColor'] = $this->hexToRgba($overlay['stroke_color']);
                } else {
                    $array['strokeColor'] = $this->hexToRgba($overlay->app->primary_color);
                }
                if (isset($overlay['stroke_width'])) {
                    $array['strokeWidth'] = $overlay['stroke_width'];
                }
                if (isset($overlay['feature_collection'])) {
                    // if the feature collection is an external geojson URL then put it in the conf file
                    if (strpos($overlay['feature_collection'], 'http') === 0 || strpos($overlay['feature_collection'], 'https') === 0) {
                        $array['url'] = $overlay['feature_collection'];
                    } else {
                        $array['url'] = route('api.export.taxonomy.getOverlaysPath', explode('/', $overlay['feature_collection']));
                    }
                }
                if (isset($overlay['configuration'])) {
                    $configuration = json_decode($overlay['configuration'], true);
                    if (is_array($configuration)) {
                        $array = array_merge($array, $configuration);
                    }
                }
                $array['type'] = 'button';

                return $array;
            }, json_decode($this->overlayLayers, true));
            array_push($data['MAP']['controls']['overlays'], ...$overlays);
        }

        // data => turn the layers (pois,tracks) off an on
        if ($this->app_pois_api_layer || $this->layers->count() > 0) {
            $data['MAP']['controls']['data'][] = ['label' => $this->getTranslations('data_label'), 'type' => 'title'];
        }
        if ($this->app_pois_api_layer) {
            $data['MAP']['controls']['data'][] = [
                'label' => $this->getTranslations('pois_data_label'),
                'type' => 'button',
                'url' => 'pois',
                'default' => $this->pois_data_default,
                'icon' => $this->pois_data_icon,
            ];
        }
        if ($this->layers->count() > 0) {
            $data['MAP']['controls']['data'][] = [
                'label' => $this->getTranslations('tracks_data_label'),
                'type' => 'button',
                'url' => 'layers',
                'default' => $this->tracks_data_default,
                'icon' => $this->tracks_data_icon,
            ];
        }

        //  Activity Filter
        if ($this->filter_activity) {
            $app_user_id = $this->user_id;
            $options = $this->get_unique_taxonomies('taxonomyActivities');

            $data['MAP']['filters']['activities'] = [
                'type' => 'select',
                'name' => $this->getTranslations('filter_activity_label'),
                'options' => $options,
            ];
        }

        //  Theme Filter
        if ($this->filter_theme) {
            $app_user_id = $this->user_id;
            $options = $this->get_unique_taxonomies('taxonomyThemes');

            $data['MAP']['filters']['themes'] = [
                'type' => 'select',
                'name' => $this->getTranslations('filter_theme_label'),
                'options' => $options,
            ];
        }

        //  Poi type Filter
        if ($this->filter_poi_type) {
            $app_user_id = $this->user_id;
            $options = [];

            $poi_types = DB::select("SELECT distinct a.id, a.identifier, a.name, a.color, a.icon from taxonomy_poi_typeables as txa inner join ec_pois as t on t.id=txa.taxonomy_poi_typeable_id inner join taxonomy_poi_types as a on a.id=taxonomy_poi_type_id where txa.taxonomy_poi_typeable_type='App\Models\EcPoi' and t.user_id=$app_user_id ORDER BY a.name ASC;");

            foreach ($poi_types as $poi_type) {
                $a = [
                    'identifier' => 'poi_type_'.$poi_type->identifier,
                    'name' => json_decode($poi_type->name, true),
                    'id' => $poi_type->id,
                    'icon' => $poi_type->icon,
                ];
                if ($poi_type->color) {
                    $a['color'] = $poi_type->color;
                }
                array_push($options, $a);
            }

            $data['MAP']['filters']['poi_types'] = [
                'type' => 'select',
                'name' => $this->getTranslations('filter_poi_type_label'),
                'options' => $options,
            ];

            // For old Applications
            // TODO: Remove it when all apps al > version .45
            $data['MAP']['filters']['poi_type'] = [
                'type' => 'select',
                'name' => $this->getTranslations('filter_poi_type_label'),
                'options' => $options,
            ];
        }

        //  Duration Filter
        if ($this->filter_track_duration) {
            $data['MAP']['filters']['track_duration'] = [
                'type' => 'slider',
                'identifier' => 'duration_forward',
                'name' => $this->getTranslations('filter_track_duration_label'),
                'units' => 'min',
                'steps' => $this->filter_track_duration_steps ?? '',
                'min' => $this->filter_track_duration_min ?? '',
                'max' => $this->filter_track_duration_max ?? '',
            ];
        }
        //  Distance Filter
        if ($this->filter_track_distance) {
            $data['MAP']['filters']['track_distance'] = [
                'type' => 'slider',
                'identifier' => 'distance',
                'name' => $this->getTranslations('filter_track_distance_label'),
                'units' => 'km',
                'steps' => $this->filter_track_distance_steps ?? '',
                'min' => $this->filter_track_distance_min ?? '',
                'max' => $this->filter_track_distance_max ?? '',
            ];
        }

        return $data;
    }

    private function config_section_theme(): array
    {
        $data = [];
        // THEME section

        $data['THEME']['fontFamilyHeader'] = $this->font_family_header;
        $data['THEME']['fontFamilyContent'] = $this->font_family_content;
        $data['THEME']['defaultFeatureColor'] = $this->default_feature_color;
        $data['THEME']['primary'] = $this->primary_color;

        return $data;
    }

    private function config_section_pages(): array
    {
        $data = [];
        // PROJECT section DEPRICATED (v1)
        if ($this->page_project) {
            $data['PROJECT']['HTML'] = $this->page_project;
        }
        // PROJECT section NEW (v2)
        if ($this->page_project) {
            $data['PROJECT']['html'] = $this->getTranslations('page_project');
        }
        // PROJECT section NEW (v2)
        if ($this->page_privacy) {
            $data['PRIVACY']['html'] = $this->getTranslations('page_privacy');
        }
        // DISCLAIMER section
        if ($this->page_disclaimer) {
            $data['DISCLAIMER']['html'] = $this->getTranslations('page_disclaimer');
        }
        // CREDITS section
        if ($this->page_credits) {
            $data['CREDITS']['html'] = $this->getTranslations('page_credits');
        }
        if ($this->page_privacy) {
            $data['PRIVACY']['html'] = $this->getTranslations('page_privacy');
        }

        return $data;
    }

    private function config_section_options(): array
    {
        $data = [];
        if (in_array($this->api, ['elbrus'])) {
            // OPTIONS section
            $data['OPTIONS']['baseUrl'] = 'https://geohub.webmapp.it/api/app/elbrus/'.$this->id.'/';
        }

        $data['OPTIONS']['startUrl'] = $this->start_url;
        $data['OPTIONS']['showEditLink'] = $this->show_edit_link;
        $data['OPTIONS']['skipRouteIndexDownload'] = $this->skip_route_index_download;
        $data['OPTIONS']['showTrackRefLabel'] = $this->show_track_ref_label;
        $data['OPTIONS']['download_track_enable'] = $this->download_track_enable;
        $data['OPTIONS']['print_track_enable'] = $this->print_track_enable;
        $data['OPTIONS']['show_searchbar'] = $this->show_search;
        $data['OPTIONS']['show_favorites'] = $this->show_favorites;
        $data['OPTIONS']['show_scale'] = $this->table_details_show_scale;
        $data['OPTIONS']['showGpxDownload'] = $this->table_details_show_gpx_download;
        $data['OPTIONS']['showKmlDownload'] = $this->table_details_show_kml_download;
        $data['OPTIONS']['showGeojsonDownload'] = (bool) $this->table_details_show_geojson_download;
        $data['OPTIONS']['showShapefileDownload'] = (bool) $this->table_details_show_shapefile_download;
        $data['OPTIONS']['showTravelMode'] = $this->show_travel_mode; // TODO: ADD2WMPACKAGE
        $data['OPTIONS']['showGetDirections'] = $this->show_get_directions; // TODO: ADD2WMPACKAGE
        $data['OPTIONS']['showFeaturesInViewport'] = $this->show_features_in_viewport; // TODO: ADD2WMPACKAGE
        $data['OPTIONS']['showMediaName'] = $this->show_media_name; // TODO: ADD2WMPACKAGE
        $data['OPTIONS']['showEmbeddedHtml'] = $this->show_embedded_html; // TODO: ADD2WMPACKAGE
        $data['OPTIONS']['maxZoomFeaturesInViewport'] = $this->max_zoom_features_in_viewport; // TODO: ADD2WMPACKAGE
        $data['OPTIONS']['minZoomFeaturesInViewport'] = $this->min_zoom_features_in_viewport; // TODO: ADD2WMPACKAGE

        foreach ($this->track_technical_details as $label => $value) {
            $label = Str::camel($label);
            $data['OPTIONS'][$label] = $value;
        }

        return $data;
    }

    private function config_section_tables(): array
    {
        $data = [];
        if (in_array($this->api, ['elbrus'])) {
            // TABLES section
            $data['TABLES']['details']['showGpxDownload'] = (bool) $this->table_details_show_gpx_download;
            $data['TABLES']['details']['showKmlDownload'] = (bool) $this->table_details_show_kml_download;
            $data['TABLES']['details']['showRelatedPoi'] = (bool) $this->table_details_show_related_poi;
            $data['TABLES']['details']['hide_duration:forward'] = ! $this->table_details_show_duration_forward;
            $data['TABLES']['details']['hide_duration:backward'] = ! $this->table_details_show_duration_backward;
            $data['TABLES']['details']['hide_distance'] = ! $this->table_details_show_distance;
            $data['TABLES']['details']['hide_ascent'] = ! $this->table_details_show_ascent;
            $data['TABLES']['details']['hide_descent'] = ! $this->table_details_show_descent;
            $data['TABLES']['details']['hide_ele:max'] = ! $this->table_details_show_ele_max;
            $data['TABLES']['details']['hide_ele:min'] = ! $this->table_details_show_ele_min;
            $data['TABLES']['details']['hide_ele:from'] = ! $this->table_details_show_ele_from;
            $data['TABLES']['details']['hide_ele:to'] = ! $this->table_details_show_ele_to;
            $data['TABLES']['details']['hide_scale'] = ! $this->table_details_show_scale;
            $data['TABLES']['details']['hide_cai_scale'] = ! $this->table_details_show_cai_scale;
            $data['TABLES']['details']['hide_mtb_scale'] = ! $this->table_details_show_mtb_scale;
            $data['TABLES']['details']['hide_ref'] = ! $this->table_details_show_ref;
            $data['TABLES']['details']['hide_surface'] = ! $this->table_details_show_surface;
            $data['TABLES']['details']['showGeojsonDownload'] = (bool) $this->table_details_show_geojson_download;
            $data['TABLES']['details']['showShapefileDownload'] = (bool) $this->table_details_show_shapefile_download;
        }

        return $data;
    }

    private function config_section_routing(): array
    {
        $data = [];
        if (in_array($this->api, ['elbrus'])) {
            // ROUTING section
            $data['ROUTING']['enable'] = $this->enable_routing;
        }

        return $data;
    }

    private function config_section_report(): array
    {
        $data = [];
        if (in_array($this->api, ['elbrus'])) {
            // REPORT SECION
            $data['REPORTS'] = $this->_getReportSection();
        }

        return $data;
    }

    private function config_section_geolocation(): array
    {
        $data = [];
        if (in_array($this->api, ['elbrus'])) {
            // GEOLOCATION SECTION
            $data['GEOLOCATION']['record']['enable'] = (bool) $this->geolocation_record_enable;
            $data['GEOLOCATION']['record']['export'] = true;
            $data['GEOLOCATION']['record']['uploadUrl'] = 'https://geohub.webmapp.it/api/usergenerateddata/store';
        } else {
            if ((bool) $this->geolocation_record_enable) {
                $data['GEOLOCATION']['record']['enable'] = (bool) $this->geolocation_record_enable;
            }
        }
        if ($this->gps_accuracy_default) {
            $data['GEOLOCATION']['gps_accuracy_default'] = $this->gps_accuracy_default;
        }

        return $data;
    }

    private function config_section_auth(): array
    {
        $data = [];
        if (in_array($this->api, ['elbrus'])) {
            // AUTH section
            $data['AUTH']['showAtStartup'] = false;
            if ($this->auth_show_at_startup) {
                $data['AUTH']['showAtStartup'] = true;
            }
            $data['AUTH']['enable'] = true;
            $data['AUTH']['loginToGeohub'] = true;
        } else {
            if ($this->auth_show_at_startup) {
                $data['AUTH']['enable'] = true;
                $data['AUTH']['showAtStartup'] = true;
            } else {
                $data['AUTH']['enable'] = false;
            }
            if ($this->webapp_auth_show_at_startup) {
                $data['AUTH']['webappEnable'] = true;
            }
        }

        return $data;
    }

    private function config_section_offline(): array
    {
        $data = [];
        // OFFLINE section
        $data['OFFLINE']['enable'] = false;
        if ($this->offline_enable) {
            $data['OFFLINE']['enable'] = true;
        }
        $data['OFFLINE']['forceAuth'] = false;
        if ($this->offline_force_auth) {
            $data['OFFLINE']['forceAuth'] = true;
        }
        $data['OFFLINE']['tracksOnPayment'] = false;
        if ($this->tracks_on_payment) {
            $data['OFFLINE']['tracksOnPayment'] = true;
        }

        return $data;
    }

    /**
     * Returns bbox array
     * [lon0,lat0,lon1,lat1]
     *
     * @param  App  $app
     */
    private function _getBBox(): array
    {
        $bbox = [];
        $q = "select ST_Extent(geometry::geometry) as bbox from ec_tracks where user_id=$this->user_id;";
        // $q = "select name,ST_AsGeojson(geometry) as bbox from ec_tracks where user_id=$app->user_id;";
        $res = DB::select($q);
        if (count($res) > 0) {
            if (! is_null($res[0]->bbox)) {
                preg_match('/\((.*?)\)/', $res[0]->bbox, $match);
                $coords = $match[1];
                $coord_array = explode(',', $coords);
                $coord_min_str = $coord_array[0];
                $coord_max_str = $coord_array[1];
                $coord_min = explode(' ', $coord_min_str);
                $coord_max = explode(' ', $coord_max_str);
                $bbox = [$coord_min[0], $coord_min[1], $coord_max[0], $coord_max[1]];
            }
        }

        return $bbox;
    }

    private function _getReportSection()
    {
        $json_string = <<<'EOT'
 {
    "enable": true,
    "url": "https://geohub.webmapp.it/api/usergenerateddata/store",
    "items": [
    {
    "title": "Crea un nuovo waypoint",
    "success": "Waypoint creato con successo",
    "url": "https://geohub.webmapp.it/api/usergenerateddata/store",
    "type": "geohub",
    "fields": [
    {
    "label": "Nome",
    "name": "title",
    "mandatory": true,
    "type": "text",
    "placeholder": "Scrivi qua il nome del waypoint"
    },
    {
    "label": "Descrizione",
    "name": "description",
    "mandatory": true,
    "type": "textarea",
    "placeholder": "Descrivi brevemente il waypoint"
    },
    {
    "label": "Foto",
    "name": "gallery",
    "mandatory": false,
    "type": "gallery",
    "limit": 5,
    "placeholder": "Aggiungi qualche foto descrittiva del waypoint"
    }
    ]
    }
    ]
    }
EOT;

        return json_decode($json_string, true);
    }

    // This functions is duplicated in the EcTrack model
    // Refactor it in a common place
    public function hexToRgba($hexColor, $opacity = 1.0)
    {
        if (empty($hexColor)) {
            return '';
        }

        if (strpos($hexColor, '#') === false) {
            return $hexColor;
        }

        $hexColor = ltrim($hexColor, '#');

        if (strlen($hexColor) === 6) {
            [$r, $g, $b] = sscanf($hexColor, '%02x%02x%02x');
        } elseif (strlen($hexColor) === 8) {
            [$r, $g, $b, $a] = sscanf($hexColor, '%02x%02x%02x%02x');
            $opacity = round($a / 255, 2);
        } else {
            throw new Exception('Invalid hex color format.');
        }

        $rgbaColor = "rgba($r, $g, $b, $opacity)";

        return $rgbaColor;
    }
}
