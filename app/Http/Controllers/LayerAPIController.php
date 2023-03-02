<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Layer;


class LayerAPIController extends Controller
{
    public function layers()
    {
        return Layer::all()->toArray();
    }
}

