<?php

namespace App\Providers;

use App\Models\App;
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
     * Return a collection of ec tracks inside the bbox. The tracks must be within $distance meters
     * of the given $trackId if provided
     *
     * @param App         $app
     * @param array       $bbox
     * @param int|null    $trackId an ec track id to reference
     * @param string|null $searchString
     * @param string      $language
     * @param int         $distanceLimit
     *
     * @return mixed
     */
    public static function getSearchClustersInsideBBox(App $app, array $bbox, int $trackId = null, string $searchString = null, string $language = 'it', int $distanceLimit = 1000): array {
        $deltaLon = ($bbox[2] - $bbox[0]) / 6;
        $deltaLat = ($bbox[3] - $bbox[1]) / 6;

        $clusterRadius = min($deltaLon, $deltaLat);

        $from = '';
        $where = '';
        $params = [$clusterRadius];
        $validTrackIds = null;

        if ($app->app_id !== 'it.webmapp.webmapp')
            $validTrackIds = $app->ecTracks->pluck('id')->toArray() ?? [];
            
        if (!is_null($validTrackIds))
            $where .= 'ec_tracks.id IN (' . join(',', $validTrackIds) . ') AND ';

        if (is_int($trackId)
            && (!$validTrackIds || in_array($trackId, $validTrackIds))) {
            $track = EcTrack::find($trackId);

            if (isset($track)) {
                $from = ', (SELECT geometry as geom FROM ec_tracks WHERE id = ?) as track';
                $params[] = $trackId;
                $where = 'ST_Distance(ST_Transform(ST_SetSRID(ec_tracks.geometry, 4326), 3857), ST_Transform(ST_SetSRID(track.geom, 4326), 3857)) <= ? AND ';
                $params[] = $distanceLimit;
            }
        }

        if (isset($searchString) && !empty($searchString)) {
            $escapedSearchString = preg_replace('/[^0-9a-z\s]/', '', strtolower($searchString));
            $where .= "to_tsvector(regexp_replace(LOWER(((ec_tracks.name::json))->>'$language'), '[^0-9a-z\s]', '', 'g')) @@ to_tsquery('$escapedSearchString') AND ";
        }

        $where .= 'geometry && ST_SetSRID(ST_MakeBox2D(ST_Point(?, ?), ST_Point(?, ?)), 4326)';
        $params = array_merge($params, $bbox);

        $query = "
SELECT
	ST_Extent(centroid) AS bbox,
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
		ST_Centroid(geometry) as centroid,
	    geometry
	FROM
		ec_tracks
	    $from
    WHERE $where
    ) clusters
GROUP BY
	cluster_id;";

        /**
         * The query calculate 5 clusters of ec tracks intersecting the given bbox.
         * For each cluster it returns:
         *  - the cluster point (geometry, geojson geometry)
         *  - the collected bbox (bbox, postgis BOX)
         *  - the list of features included in the cluster (ids, json array)
         */
        $res = DB::select($query, $params);
        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => []
        ];

        foreach ($res as $cluster) {
            $ids = json_decode($cluster->ids, true);
            $geometry = json_decode($cluster->geometry, true);
            $bboxString = str_replace(',', ' ', str_replace(['B', 'O', 'X', '(', ')'], '', $cluster->bbox));
            $bbox = array_map('floatval', explode(' ', $bboxString));

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
                    "images" => $images,
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
