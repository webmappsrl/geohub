<?php

namespace App\Http\Controllers;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Http\Request;
use App\Models\TaxonomyWhere;

class TaxonomyWhereController extends Controller
{
    use GeometryFeatureTrait;

    /**
     * Get TaxonomyWhere by ID as geoJson
     * @param int $id the TaxonomyWhere id
     *
     * @return mixed return the TaxonomyWhere geoJson
     *
     */
    public function getGeometryFromTaxonomyWhere(int $id)
    {
        $taxonomyWhere = TaxonomyWhere::find($id)->getGeojson();

        return response()->json($taxonomyWhere, 200);
    }
}
