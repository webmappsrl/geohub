<?php

namespace App\Http\Controllers;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Http\JsonResponse;
use App\Models\TaxonomyActivity;

class TaxonomyActivityController extends Controller
{
    use GeometryFeatureTrait;

    /**
     * Get TaxonomyActivity by ID
     *
     * @param int $id the TaxonomyActivity id
     *
     * @return JsonResponse return the TaxonomyActivity
     *
     */
    public function getTaxonomyActivity(int $id): JsonResponse
    {
        $taxonomyActivity = TaxonomyActivity::find($id);
        if (is_null($taxonomyActivity)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return response()->json($taxonomyActivity, 200);
    }

    /**
     * Get TaxonomyActivity by Identifier
     *
     * @param string $identifier the TaxonomyActivity identifier
     *
     * @return JsonResponse return the TaxonomyActivity
     *
     */
    public function getTaxonomyActivityFromIdentifier(string $identifier): JsonResponse
    {
        $taxonomyActivity = TaxonomyActivity::where('identifier', $identifier)->first();
        if (is_null($taxonomyActivity)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return response()->json($taxonomyActivity, 200);
    }
    /**
     * Get All TaxonomyActivities
     * 
     * @return JsonResponse return all TaxonomyActivities
     */
    public function exportAllActivities(): JsonResponse
    {
        $taxonomyActivities = TaxonomyActivity::select('id', 'name', 'identifier')->get();
        if (is_null($taxonomyActivities)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return response()->json($taxonomyActivities, 200);
    }
}
