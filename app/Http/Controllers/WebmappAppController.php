<?php

namespace App\Http\Controllers;

use App\Models\EcTrack;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyWhere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebmappAppController extends Controller {
    public function search(Request $request): JsonResponse {
        $searchString = $request->get('string');
        $language = $request->get('language');

        if (!isset($searchString) || empty($searchString))
            return response()->json(['error' => 'Missing parameter: string'], 400);

        if (!isset($language) || empty($language))
            $language = 'it';
        else
            $language = substr($language, 0, 2);

        $escapedSearchString = preg_replace('/[^0-9a-z\s]/', '', strtolower($searchString));
        $escapedSearchString = implode(':* || ', explode(' ', $escapedSearchString)) . ':*';

        return response()->json([
            'places' => $this->getPlacesSearchSection($escapedSearchString, $language),
            'ec_tracks' => $this->getEcTracksSearchSection($escapedSearchString, $language),
            'poi_types' => $this->getPoiTypesSearchSection($escapedSearchString, $language),
        ], 200);
    }

    /**
     * Get the places related to the search string
     *
     * @param string $escapedSearchString
     * @param string $language
     *
     * @return array with the simplified information - id, name, bbox
     */
    public function getPlacesSearchSection(string $escapedSearchString, string $language): array {
        $query = $this->getSearchQuery('taxonomy_wheres', $escapedSearchString, $language);
        $results = DB::select($query);

        $places = [];
        foreach ($results as $row) {
            $where = TaxonomyWhere::find($row->id);

            $places[] = [
                "id" => $where->id,
                "name" => $where->getTranslation('name', $language),
                "bbox" => $where->bbox()
            ];
        }

        return $places;
    }

    /**
     * Get the tracks related to the search string
     *
     * @param string $escapedSearchString
     * @param string $language
     *
     * @return array with the simplified information - id, name, image, wheres
     */
    public function getEcTracksSearchSection(string $escapedSearchString, string $language): array {
        $query = $this->getSearchQuery('ec_tracks', $escapedSearchString, $language);
        $results = DB::select($query);

        $tracks = [];
        foreach ($results as $row) {
            $track = EcTrack::find($row->id);

            $obj = [
                "id" => $track->id,
                "name" => $track->getTranslation('name', $language),
                "where" => $track->taxonomyWheres()->pluck('id')
            ];

            if (isset($track->featureImage))
                $obj['image'] = $track->featureImage;

            $tracks[] = $obj;
        }

        return $tracks;
    }

    /**
     * Get the poi types related to the search string
     *
     * @param string $escapedSearchString
     * @param string $language
     *
     * @return array with the simplified information - id, name, bbox
     */
    public function getPoiTypesSearchSection(string $escapedSearchString, string $language): array {
        $query = $this->getSearchQuery('taxonomy_poi_types', $escapedSearchString, $language);
        $results = DB::select($query);

        $poiTypes = [];
        foreach ($results as $row) {
            $poiType = TaxonomyPoiType::find($row->id);

            $poiTypes[] = [
                "id" => $poiType->id,
                "name" => $poiType->getTranslation('name', $language)
            ];
        }

        return $poiTypes;
    }

    public function getSearchQuery(string $table, string $escapedSearchString, string $language, array $columnToCheck = []): string {
        return "SELECT id
            FROM " . $table . ",
                 to_tsvector(
                    regexp_replace(
                        LOWER(
                            ((" . $table . ".name::json))->>'" . $language . "'
                        ),
                        '[^0-9a-z\s]', '', 'g'
                    )
                ) as documentNameVector,
                to_tsquery('" . $escapedSearchString . "') as searchQuery
            WHERE documentNameVector @@ searchQuery 
            ORDER BY ts_rank_cd(documentNameVector, searchQuery)
            LIMIT 5;";
    }
}
