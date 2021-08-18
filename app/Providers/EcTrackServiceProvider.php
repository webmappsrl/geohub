<?php

namespace App\Providers;

use App\Models\EcTrack;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class EcTrackServiceProvider extends ServiceProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(EcTrackServiceProvider::class, function ($app) {
            return new EcTrackServiceProvider($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        //
    }

    /**
     * Return a collection of up to $limit ec tracks inside the bbox
     *
     * @param array $bbox
     *
     * @return mixed
     */
    public static function getSearchClustersInsideBBox(array $bbox): array {
        $count = EcTrack::whereRaw('geometry && ST_SetSRID (ST_MakeBox2D (ST_Point (?, ?), ST_Point (?, ?)), 4326)', $bbox)->count();
        $query = '
SELECT
    ST_AsGeojson(ST_Centroid(ST_Collect(geometry))) AS geometry,
    json_agg(id) as ids,
    ST_Extent(geometry) AS bbox
FROM (
  SELECT
	(ST_ClusterKMeans(
		geometry,
		LEAST(5, ?)
	) OVER()) as kmeans,
	geometry,
	id
    FROM
	    ec_tracks
    WHERE geometry && ST_SetSRID (ST_MakeBox2D(ST_Point(?, ?), ST_Point(?, ?)), 4326)
) AS ksub
GROUP BY kmeans
ORDER BY kmeans;';

        /**
         * The query calculate 5 clusters of ec tracks intersecting the given bbox.
         * For each cluster it returns:
         *  - the cluster point (geometry, geojson geometry)
         *  - the collected bbox (bbox, postgis BOX)
         *  - the list of features included in the cluster (ids, json array)
         */
        $res = DB::select($query, array_merge([$count], $bbox));
        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => []
        ];

        foreach ($res as $cluster) {
            $bboxString = str_replace(',', ' ', str_replace(['B', 'O', 'X', '(', ')'], '', $cluster->bbox));
            $bbox = array_map('floatval', explode(' ', $bboxString));
            $ids = json_decode($cluster->ids, true);
            $geometry = json_decode($cluster->geometry, true);

            $images = [];
            $i = 0;

            while ($i < count($ids) && count($images) < 3) {
                $track = EcTrack::find($ids[$i]);

                $image = isset($track->featureImage) ? $track->featureImage->thumbnail('150x150') : '';
                if (isset($image) && !empty($image) && !in_array($image, $images))
                    $images[] = $image;
                $i++;
            }

            $featureCollection['features'][] = [
                "type" => "Feature",
                "geometry" => $geometry,
                "properties" => [
                    "ids" => $ids,
                    "bbox" => $bbox,
                    "images" => $images
                ]
            ];
        }

        return $featureCollection;
    }
}
