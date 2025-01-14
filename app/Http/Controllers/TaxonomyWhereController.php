<?php

namespace App\Http\Controllers;

use App\Models\TaxonomyWhere;
use App\Traits\GeometryFeatureTrait;
use Illuminate\Http\JsonResponse;

class TaxonomyWhereController extends Controller
{
    use GeometryFeatureTrait;

    /**
     * Get TaxonomyWhere by ID as geoJson
     *
     * @param  int  $id  the TaxonomyWhere id
     * @return JsonResponse return the TaxonomyWhere geoJson
     */
    public function getGeoJsonFromTaxonomyWhere(int $id): JsonResponse
    {
        $taxonomyWhere = TaxonomyWhere::find($id);
        $taxonomyWhere = ! is_null($taxonomyWhere) ? $taxonomyWhere->getGeojson() : null;
        if (is_null($taxonomyWhere)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }

        return response()->json($taxonomyWhere, 200);
    }

    /**
     * Get TaxonomyWhere by Identifier as geoJson
     *
     * @param  string  $identifier  the TaxonomyWhere identifier
     * @return JsonResponse return the TaxonomyWhere geoJson
     */
    public function getGeoJsonFromTaxonomyWhereIdentifier(string $identifier): JsonResponse
    {
        $taxonomyWhere = TaxonomyWhere::where('identifier', $identifier)->first();
        $taxonomyWhere = ! is_null($taxonomyWhere) ? $taxonomyWhere->getGeojson() : null;
        if (is_null($taxonomyWhere)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }

        return response()->json($taxonomyWhere, 200);
    }

    /**
     * Get TaxonomyWhere by ID
     *
     * @param  int  $id  the TaxonomyWhere id
     * @return JsonResponse return the TaxonomyWhere
     */
    public function getTaxonomyWhere(int $id): JsonResponse
    {
        $taxonomyWhere = TaxonomyWhere::find($id);
        if (is_null($taxonomyWhere)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }

        return response()->json($taxonomyWhere, 200);
    }

    /**
     * Get TaxonomyWhere by Identifier
     *
     * @param  string  $identifier  the TaxonomyWhere identifier
     * @return JsonResponse return the TaxonomyWhere
     */
    public function getTaxonomyWhereFromIdentifier(string $identifier): JsonResponse
    {
        $taxonomyWhere = TaxonomyWhere::where('identifier', $identifier)->first();
        if (is_null($taxonomyWhere)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }

        return response()->json($taxonomyWhere, 200);
    }
}
