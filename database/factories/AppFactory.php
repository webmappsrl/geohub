<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppFactory extends Factory
{
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
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'app_id' => 'it.webmapp.'.$this->faker->unique()->slug(),
            'customer_name' => $this->faker->name(),
            'map_max_zoom' => $this->faker->numberBetween(15, 19),
            'map_def_zoom' => $this->faker->numberBetween(12, 14),
            'map_min_zoom' => $this->faker->numberBetween(9, 11),
            'map_bbox' => json_encode([$this->faker->numberBetween(15, 19), $this->faker->numberBetween(15, 19), $this->faker->numberBetween(15, 19), $this->faker->numberBetween(15, 19)]),
            'font_family_header' => 'Roboto',
            'font_family_content' => 'Roboto',
            'default_feature_color' => $this->faker->hexColor(),
            'primary_color' => $this->faker->hexColor(),
            'start_url' => '/main/explore',
            'show_edit_link' => $this->faker->boolean(),
            'skip_route_index_download' => $this->faker->boolean(),
            'poi_min_radius' => 0.5,
            'poi_max_radius' => 1.2,
            'poi_icon_zoom' => 16,
            'poi_icon_radius' => 1,
            'poi_min_zoom' => 13,
            'poi_label_min_zoom' => 10.5,
            'show_track_ref_label' => $this->faker->boolean(),
            'table_details_show_gpx_download' => $this->faker->boolean(),
            'table_details_show_kml_download' => $this->faker->boolean(),
            'table_details_show_related_poi' => $this->faker->boolean(),
            'enable_routing' => $this->faker->boolean(),
            'user_id' => User::all()->random()->id,
            'table_details_show_duration_forward' => $this->faker->boolean(),
            'table_details_show_duration_backward' => $this->faker->boolean(),
            'table_details_show_distance' => $this->faker->boolean(),
            'table_details_show_ascent' => $this->faker->boolean(),
            'table_details_show_descent' => $this->faker->boolean(),
            'table_details_show_ele_max' => $this->faker->boolean(),
            'table_details_show_ele_min' => $this->faker->boolean(),
            'table_details_show_ele_from' => $this->faker->boolean(),
            'table_details_show_ele_to' => $this->faker->boolean(),
            'table_details_show_scale' => $this->faker->boolean(),
            'table_details_show_cai_scale' => $this->faker->boolean(),
            'table_details_show_mtb_scale' => $this->faker->boolean(),
            'table_details_show_ref' => $this->faker->boolean(),
            'table_details_show_surface' => $this->faker->boolean(),
            'table_details_show_geojson_download' => $this->faker->boolean(),
            'table_details_show_shapefile_download' => $this->faker->boolean(),
            'start_end_icons_show' => $this->faker->boolean(),
            'start_end_icons_min_zoom' => $this->faker->numberBetween(10, 20),
            'ref_on_track_show' => $this->faker->boolean(),
            'ref_on_track_min_zoom' => $this->faker->numberBetween(10, 20),
            'tiles' => json_encode([$this->faker->url(), $this->faker->url()]),
        ];
    }
}
