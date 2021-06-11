<?php

namespace App\Http\Controllers;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Http\JsonResponse;
use App\Models\TaxonomyTarget;

class TaxonomyTargetController extends Controller
{
    use GeometryFeatureTrait;

    /**
     * Get TaxonomyTarget by ID
     *
     * @param int $id the TaxonomyTarget id
     *
     * @return JsonResponse return the TaxonomyTarget
     *
     */
    public function getTaxonomyTarget(int $id): JsonResponse
    {
        $taxonomyTarget = TaxonomyTarget::find($id);
        if (is_null($taxonomyTarget)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return response()->json($taxonomyTarget, 200);
    }

    /**
     * Get TaxonomyTarget by Identifier
     *
     * @param string $identifier the TaxonomyTarget identifier
     *
     * @return JsonResponse return the TaxonomyTarget
     *
     */
    public function getTaxonomyTargetFromIdentifier(string $identifier): JsonResponse
    {
        $taxonomyTarget = TaxonomyTarget::where('identifier', $identifier)->first();
        if (is_null($taxonomyTarget)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return response()->json($taxonomyTarget, 200);
    }
}
