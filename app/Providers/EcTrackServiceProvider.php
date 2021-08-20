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
        //        $count = EcTrack::whereRaw('geometry && ST_SetSRID (ST_MakeBox2D (ST_Point (?, ?), ST_Point (?, ?)), 4326)', $bbox)->count();
        //        $oldQuery = '
        //SELECT
        //    ST_AsGeojson(ST_Centroid(ST_Collect(geometry))) AS geometry,
        //    json_agg(id) as ids,
        //    ST_Extent(geometry) AS bbox
        //FROM (
        //  SELECT
        //	(ST_ClusterKMeans(
        //		geometry,
        //		LEAST(5, ?)
        //	) OVER()) as kmeans,
        //	geometry,
        //	id
        //    FROM
        //	    ec_tracks
        //    WHERE geometry && ST_SetSRID (ST_MakeBox2D(ST_Point(?, ?), ST_Point(?, ?)), 4326)
        //) AS ksub
        //GROUP BY kmeans
        //ORDER BY kmeans;';

        $deltaLon = ($bbox[2] - $bbox[0]) / 5;
        $deltaLat = ($bbox[3] - $bbox[1]) / 5;

        $clusterRadius = min($deltaLon, $deltaLat);

        $query = '
SELECT
	ST_Extent(geometry) AS bbox,
--    ST_AsGeojson(ST_Centroid(ST_Collect(geometry))) AS geometry,
    ST_AsGeojson(ST_Centroid(ST_Extent(geometry))) AS geometry,
	json_agg(id) AS ids
FROM (
	SELECT
		id,
		ST_ClusterDBSCAN(
		    ST_Centroid(geometry),
			eps := ?,
			minpoints := 1
		) OVER () AS cluster_id,
		geometry
	FROM
		ec_tracks
    WHERE geometry && ST_SetSRID (ST_MakeBox2D(ST_Point(?, ?), ST_Point(?, ?)), 4326)
    ) sq
GROUP BY
	cluster_id;';

        /**
         * The query calculate 5 clusters of ec tracks intersecting the given bbox.
         * For each cluster it returns:
         *  - the cluster point (geometry, geojson geometry)
         *  - the collected bbox (bbox, postgis BOX)
         *  - the list of features included in the cluster (ids, json array)
         */
        //        $res = DB::select($query, array_merge([$count], $bbox));
        $res = DB::select($query, array_merge([$clusterRadius], $bbox));
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

    /**
     * Retrieve the $limit closest track to the given location
     *
     * @param float $lon
     * @param float $lat
     * @param int   $distance the distance limit in meters
     * @param int   $limit
     *
     * @return array
     */
    public static function getNearestToLonLat(float $lon, float $lat, int $distance = 10000, int $limit = 5): array {
        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => []
        ];
        $tracks = EcTrack::whereRaw("ST_Distance(
                ST_Transform('SRID=4326;POINT($lon $lat)'::geometry, 3857),
                ST_Transform(ST_SetSRID(geometry, 4326), 3857)
                ) <= $distance")
            ->orderByRaw("ST_Distance(
                ST_Transform('SRID=4326;POINT($lon $lat)'::geometry, 3857),
                ST_Transform(ST_SetSRID(geometry, 4326), 3857)
                ) ASC")
            ->limit($limit)
            ->get();

        foreach ($tracks as $track) {
            $featureCollection['features'][] = $track->getGeojson();
        }

        return $featureCollection;
    }

    /**
     * Retrieves the $limit most viewed ec tracks
     *
     * @param int $limit
     *
     * @return array
     */
    // TODO: select the most viewed tracks from a real analytic value and not randomly
    public static function getMostViewed(int $limit = 5): array {
        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => []
        ];

        $tracks = EcTrack::limit($limit)->get();

        foreach ($tracks as $track) {
            $featureCollection['features'][] = $track->getGeojson();
        }

        return $featureCollection;
    }
}
