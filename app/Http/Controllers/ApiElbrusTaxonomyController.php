<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyTarget;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhere;
use App\Models\TaxonomyWhen;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TaxonomyPoiTypes;

class ApiElbrusTaxonomyController extends Controller
{
    private $names = [
        'activity', 'where', 'when', 'who', 'theme', 'webmapp_category'
    ];

    public function getTerms(int $app_id, string $taxonomy_name): JsonResponse
    {
        $json = [];
        $code = 200;
        if (!in_array($taxonomy_name, $this->names)) {
            $code = 400;
            $json = ['code' => $code, 'error' => 'Taxonomy name not valid'];
            return response()->json($json, $code);
        }
        $app = App::find($app_id);
        if (is_null($app)) {
            $code = 404;
            $json = ['code' => $code, 'App NOT found'];
            return response()->json($json, $code);
        }

        $terms = $this->_termsByUserId($app, $taxonomy_name);

        if (count($terms) > 0) {
            foreach ($terms as $tid => $items) {
                switch ($taxonomy_name) {
                    case 'activity':
                        $tax = TaxonomyActivity::find($tid)->toArray();
                        break;
                    case 'theme':
                        $tax = TaxonomyTheme::find($tid)->toArray();
                        break;
                    case 'where':
                        $tax = TaxonomyWhere::find($tid)->toArray();
                        unset($tax['geometry']);
                        break;
                    case 'who':
                        $tax = TaxonomyTarget::find($tid)->toArray();
                        break;
                    case 'when':
                        $tax = TaxonomyWhen::find($tid)->toArray();
                        break;
                    case 'webmapp_category':
                        $tax = TaxonomyPoiType::find($tid)->toArray();
                        break;
                }
                $tax['items'] = $items;
                $tax['id'] = $taxonomy_name . '_' . $tid;
                $json[$taxonomy_name . '_' . $tid] = $tax;
            }
        }

        return response()->json($json, $code);
    }

    private function _termsByUserId($app, $taxonomy_name)
    {
        $terms = [];
        $add_poi_types = false;
        switch ($taxonomy_name) {
            case 'activity':
                $table = 'taxonomy_activityables';
                $tid = 'taxonomy_activity_id';
                $fid = 'taxonomy_activityable_id';
                $type = 'taxonomy_activityable_type';
                break;
            case 'theme':
                $table = 'taxonomy_themeables';
                $tid = 'taxonomy_theme_id';
                $fid = 'taxonomy_themeable_id';
                $type = 'taxonomy_themeable_type';
                break;
            case 'who':
                $table = 'taxonomy_targetables';
                $tid = 'taxonomy_target_id';
                $fid = 'taxonomy_targetable_id';
                $type = 'taxonomy_targetable_type';
                break;
            case 'when':
                $table = 'taxonomy_whenables';
                $tid = 'taxonomy_when_id';
                $fid = 'taxonomy_whenable_id';
                $type = 'taxonomy_whenable_type';
                break;
            case 'where':
                $table = 'taxonomy_whereables';
                $tid = 'taxonomy_where_id';
                $fid = 'taxonomy_whereable_id';
                $type = 'taxonomy_whereable_type';
                break;
            case 'webmapp_category':
                $table = 'taxonomy_poi_typeables';
                $tid = 'taxonomy_poi_type_id';
                $fid = 'taxonomy_poi_typeable_id';
                $type = 'taxonomy_poi_typeable_type';
                $add_poi_types = true;
                break;
            default:
                $table = 'taxonomy_ables';
                $tid = 'taxonomy_id';
                $fid = 'taxonomy_able_id';
                $type = 'taxonomy_able_type';
        }
        $res = DB::select("
            SELECT $tid as tid, $fid as fid 
            FROM $table 
            WHERE $type='App\Models\EcTrack' 
            AND $fid IN (select id from ec_tracks where user_id=$app->user_id)
         ");
        if (count($res) > 0) {
            foreach ($res as $item) {
                $terms[$item->tid]['track'][] = 'ec_track_' . $item->fid;
            }
        }

        if ($add_poi_types) {
            $res = DB::select("
                SELECT $tid as tid, $fid as fid 
                FROM $table 
                WHERE $type='App\Models\EcPoi' 
                AND $fid IN (select id from ec_pois where user_id=$app->user_id)
            ");

            if (count($res) > 0) {
                foreach ($res as $item) {
                    $terms[$item->tid]['poi'][] = 'ec_poi_' . $item->fid;
                }
            }
        }

        return $terms;
    }

    /**
     * Update the specified user.
     *
     * @param  int  $app_id
     * @param  string  $taxonomy_name
     * @param  int  $term_id
     * @return JsonResponse
     */
    public function getTracksByAppAndTerm(int $app_id, string $taxonomy_name, int $term_id): JsonResponse
    {
        $json = [];
        $code = 200;

        $json['tracks'] = [];

        if (!in_array($taxonomy_name, $this->names)) {
            $code = 400;
            $json = ['code' => $code, 'error' => 'Taxonomy name not valid'];
            return response()->json($json, $code);
        }

        $app = App::find($app_id);
        if (is_null($app)) {
            $code = 404;
            $json = ['code' => $code, 'App NOT found'];
            return response()->json($json, $code);
        }

        $term = $this->_getTermByTaxonomy($taxonomy_name, $term_id);
        if (is_null($term)) {
            $code = 404;
            $json = ['code' => $code, 'Term NOT found in taxonomy ' . $taxonomy_name];
            return response()->json($json, $code);
        }

        $tracks = $app->listTracksByTerm($term, $taxonomy_name);

        return response()->json($tracks, $code);
    }

    protected function _getTermByTaxonomy(string $taxonomy_name, int $term_id)
    {
        $tax = null;

        switch ($taxonomy_name) {
            case 'activity':
                $term = TaxonomyActivity::find($term_id);
                break;
            case 'theme':
                $term = TaxonomyTheme::find($term_id);
                break;
            case 'where':
                $term = TaxonomyWhere::find($term_id);
                unset($term['geometry']);
                break;
            case 'who':
                $term = TaxonomyTarget::find($term_id);
                break;
            case 'when':
                $term = TaxonomyWhen::find($term_id);
                break;
            case 'webmapp_category':
                $term = TaxonomyPoiType::find($term_id);
                break;
        }

        if ($term) {
            $tax = $term->toArray();
        }

        return $tax;
    }
}
