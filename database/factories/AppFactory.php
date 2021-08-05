<?php

namespace Database\Factories;

use App\Models\App;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = App::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'name' => $this->faker->name(),
            'app_id' => 'it.webmapp.' . $this->faker->unique()->slug(),
            'customerName' => $this->faker->name(),
            'maxZoom' => $this->faker->numberBetween(15, 19),
            'defZoom' => $this->faker->numberBetween(12, 14),
            'minZoom' => $this->faker->numberBetween(9, 11),
            'fontFamilyHeader' => 'Roboto',
            'fontFamilyContent' => 'Roboto',
            'defaultFeatureColor' => $this->faker->hexColor(),
            'primary' => $this->faker->hexColor(),
            'startUrl' => '/main/explore',
            'showEditLink' => false,
            'skipRouteIndexDownload' => true,
            'poiMinRadius' => 0.5,
            'poiMaxRadius' => 1.2,
            'poiIconZoom' => 16,
            'poiIconRadius' => 1,
            'poiMinZoom' => 13,
            'poiLabelMinZoom' => 10.5,
            'showTrackRefLabel' => false,
            'showGpxDownload' => false,
            'showKmlDownload' => false,
            'showRelatedPoi' => false,
            'enableRouting' => false,
            'user_id' => \App\Models\User::all()->random()->id,
            'table_details_show_duration_forward' => true,
            'table_details_show_duration_backward' => false,
            'table_details_show_distance' => true,
            'table_details_show_ascent' => true,
            'table_details_show_descent' => true,
            'table_details_show_ele_max' => true,
            'table_details_show_ele_min' => true,
            'table_details_show_ele_from' => false,
            'table_details_show_ele_to' => false,
            'table_details_show_scale' => true,
            'table_details_show_cai_scale' => false,
            'table_details_show_mtb_scale' => false,
            'table_details_show_ref' => true,
            'table_details_show_surface' => false,
            'table_details_show_geojson_download' => false,
            'table_details_show_shapefile_download' => false
        ];
    }
}
