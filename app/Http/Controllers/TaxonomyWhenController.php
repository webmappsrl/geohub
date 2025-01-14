<?php

namespace App\Http\Controllers;

use App\Models\TaxonomyWhen;
use App\Traits\GeometryFeatureTrait;
use Illuminate\Http\JsonResponse;

class TaxonomyWhenController extends Controller
{
    use GeometryFeatureTrait;

    /**
     * Get TaxonomyWhen by ID
     *
     * @param  int  $id  the TaxonomyWhen id
     * @return JsonResponse return the TaxonomyWhen
     */
    public function getTaxonomyWhen(int $id): JsonResponse
    {
        $taxonomyWhen = TaxonomyWhen::find($id);
        if (is_null($taxonomyWhen)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }

        return response()->json($taxonomyWhen, 200);
    }

    /**
     * Get TaxonomyWhen by Identifier
     *
     * @param  string  $identifier  the TaxonomyWhen identifier
     * @return JsonResponse return the TaxonomyWhen
     */
    public function getTaxonomyWhenFromIdentifier(string $identifier): JsonResponse
    {
        $taxonomyWhen = TaxonomyWhen::where('identifier', $identifier)->first();
        if (is_null($taxonomyWhen)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }

        return response()->json($taxonomyWhen, 200);
    }
}
