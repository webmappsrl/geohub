<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      x={
 *          "logo": {
 *              "url": "http://localhost:8000/assets/images/logo_sosec.jpg"
 *          }
 *      },
 *      title="OSM2CAI",
 *      description="Api documentation",
 *      @OA\Contact(
 *          email="catastorei@cai.it"
 *      ),
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
