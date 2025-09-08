<?php

namespace App\Traits;

use App\Providers\WebmappAppIconProvider;

trait IconTrait
{
    use ConfTrait;

    public function icons()
    {
        $iconProvider = new WebmappAppIconProvider();
        $icons = [];

        $taxonomy_activities = $this->get_unique_taxonomies_from_layers_and_tracks('taxonomyActivities');
        $taxonomy_themes = $this->get_unique_taxonomies_from_layers_and_tracks('taxonomyThemes');
        $taxonomy_poi_types = $this->get_unique_poi_types_taxonomies($this->user_id);
        $all_taxonomies = array_merge($taxonomy_activities, $taxonomy_themes, $taxonomy_poi_types);
        foreach ($all_taxonomies as $taxonomy) {
            if (! isset($taxonomy['icon'])) {
                continue;
            }
            $label = $iconProvider->getIdentifier($taxonomy['icon']);
            if (! is_null($label)) {
                $icons[$label] = $taxonomy['icon'];
            }
        }

        return $icons;
    }
}
