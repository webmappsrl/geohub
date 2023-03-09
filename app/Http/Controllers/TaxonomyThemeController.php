<?php

namespace App\Http\Controllers;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Http\JsonResponse;
use App\Models\TaxonomyTheme;

class TaxonomyThemeController extends Controller
{
    use GeometryFeatureTrait;

    /**
     * Get TaxonomyTheme by ID
     *
     * @param int $id the TaxonomyTheme id
     *
     * @return JsonResponse return the TaxonomyTheme
     *
     */
    public function getTaxonomyTheme(int $id): JsonResponse
    {
        $taxonomyTheme = TaxonomyTheme::find($id);
        if (is_null($taxonomyTheme)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return response()->json($taxonomyTheme, 200);
    }

    /**
     * Get TaxonomyTheme by Identifier
     *
     * @param string $identifier the TaxonomyTheme identifier
     *
     * @return JsonResponse return the TaxonomyTheme
     *
     */
    public function getTaxonomyThemeFromIdentifier(string $identifier): JsonResponse
    {
        $taxonomyTheme = TaxonomyTheme::where('identifier', $identifier)->first();
        if (is_null($taxonomyTheme)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return response()->json($taxonomyTheme, 200);
    }
    
    /**
     * Get All TaxonomyThemes
     *
     * @return JsonResponse return all TaxonomyThemes
     *
     */
    public function exportAllThemes(): JsonResponse
    {
        $taxonomyThemes = TaxonomyTheme::select('id','name','identifier')->get();
        if (is_null($taxonomyThemes)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return response()->json($taxonomyThemes, 200);
    }
}
