<?php

namespace App\Traits;

use App\Services\AppIconService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

trait IconTrait
{

    public function icons()
    {
        $iconService = app(AppIconService::class);
        $icons = [];

        $this->collect_layer_taxonomy_icons($iconService, $icons);
        $this->collect_track_and_poi_taxonomy_icons($iconService, $icons);

        return $icons;
    }

    private function collect_layer_taxonomy_icons(AppIconService $iconService, array &$icons): void
    {
        if ($this->layers->isNotEmpty()) {
            $taxonomyTypes = ['Activities', 'Themes'];
            foreach ($taxonomyTypes as $type) {
                $relation = "taxonomy{$type}";
                $layerTaxonomies = $this->layers->load($relation)
                    ->pluck($relation)
                    ->flatten()
                    ->filter(function ($taxonomy) {
                        return isset($taxonomy['icon']);
                    });

                $this->add_taxonomy_icons($layerTaxonomies, $iconService, $icons);
            }
        }
    }

    private function collect_track_and_poi_taxonomy_icons(AppIconService $iconService, array &$icons): void
    {
        $taxonomyQueries = [
            [
                'table' => 'taxonomy_activities',
                'relation_table' => 'taxonomy_activityables',
                'relation_id' => 'taxonomy_activity_id',
                'morphable_id' => 'taxonomy_activityable_id',
                'morphable_type' => 'taxonomy_activityable_type',
                'model_type' => 'App\\Models\\EcTrack',
                'model_table' => 'ec_tracks'
            ],
            [
                'table' => 'taxonomy_themes',
                'relation_table' => 'taxonomy_themeables',
                'relation_id' => 'taxonomy_theme_id',
                'morphable_id' => 'taxonomy_themeable_id',
                'morphable_type' => 'taxonomy_themeable_type',
                'model_type' => 'App\\Models\\EcTrack',
                'model_table' => 'ec_tracks'
            ],
            [
                'table' => 'taxonomy_poi_types',
                'relation_table' => 'taxonomy_poi_typeables',
                'relation_id' => 'taxonomy_poi_type_id',
                'morphable_id' => 'taxonomy_poi_typeable_id',
                'morphable_type' => 'taxonomy_poi_typeable_type',
                'model_type' => 'App\\Models\\EcPoi',
                'model_table' => 'ec_pois'
            ]
        ];

        foreach ($taxonomyQueries as $query) {
            $taxonomies = DB::table($query['relation_table'])
                ->join($query['table'], "{$query['table']}.id", '=', "{$query['relation_table']}.{$query['relation_id']}")
                ->join($query['model_table'], "{$query['model_table']}.id", '=', "{$query['relation_table']}.{$query['morphable_id']}")
                ->where("{$query['relation_table']}.{$query['morphable_type']}", $query['model_type'])
                ->where("{$query['model_table']}.user_id", $this->user_id)
                ->whereNotNull("{$query['table']}.icon")
                ->select("{$query['table']}.icon")
                ->distinct()
                ->get();

            $this->add_taxonomy_icons($taxonomies, $iconService, $icons);
        }
    }

    private function add_taxonomy_icons(Collection $taxonomies, AppIconService $iconService, array &$icons): void
    {
        foreach ($taxonomies as $taxonomy) {
            $label = $iconService->getIconByIdentifier($taxonomy->icon);
            if (!is_null($label)) {
                $icons[$label] = $taxonomy->icon;
            }
        }
    }
}
