<?php

namespace App\Http\Controllers;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\TaxonomyWhere;
use function PHPUnit\Framework\isNull;

class TaxonomyWhereController extends Controller
{
    use GeometryFeatureTrait;

    /**
     * Get TaxonomyWhere by ID as geoJson
     * @param int $id the TaxonomyWhere id
     *
     * @return JsonResponse return the TaxonomyWhere geoJson
     *
     */
    public function getGeoJsonFromTaxonomyWhere(int $id): JsonResponse
    {
        $taxonomyWhere = TaxonomyWhere::find($id);
        $taxonomyWhere = !is_null($taxonomyWhere) ? $taxonomyWhere->getGeojson() : null;
        if (is_null($taxonomyWhere))
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);

        return response()->json($taxonomyWhere, 200);
    }
}
