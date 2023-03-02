<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Layer;


class LayerAPIController extends Controller
{
    public function layers()
    {
        foreach(Layer::all()->toArray() as $layer) {
           unset($layer['taxonomy_themes']);
           unset($layer['taxonomy_wheres']);
           unset($layer['taxonomy_activities']);
           $layers[] = $layer;
        }            
        return $layers;
    }
}

