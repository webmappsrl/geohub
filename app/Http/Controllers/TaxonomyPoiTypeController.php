<?php

namespace App\Http\Controllers;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Http\JsonResponse;
use App\Models\TaxonomyPoiType;

class TaxonomyPoiTypeController extends Controller
{
    use GeometryFeatureTrait;

    /**
     * Get TaxonomyPoiType by ID
     *
     * @param int $id the TaxonomyPoiType id
     *
     * @return JsonResponse return the TaxonomyPoiType geoJson
     *
     */
    public function getTaxonomyPoiType(int $id): JsonResponse
    {
        $taxonomyPoiType = TaxonomyPoiType::find($id);
        if (is_null($taxonomyPoiType)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return response()->json($taxonomyPoiType, 200);
    }

    /**
     * Get TaxonomyPoiType by Identifier
     *
     * @param string $identifier the TaxonomyPoiType identifier
     *
     * @return JsonResponse return the TaxonomyPoiType geoJson
     *
     */
    public function getTaxonomyPoiTypeFromIdentifier(string $identifier): JsonResponse
    {
        $taxonomyPoiType = TaxonomyPoiType::where('identifier', $identifier)->first();
        if (is_null($taxonomyPoiType)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return response()->json($taxonomyPoiType, 200);
    }
}
