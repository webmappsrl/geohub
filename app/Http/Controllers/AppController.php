<?php

namespace App\Http\Controllers;

use App\Models\App;
use Illuminate\Http\Request;

class AppController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\App  $app
     * @return \Illuminate\Http\Response
     */
    public function config(int $id)
    {
        $app = App::find($id);
        if(is_null($app)) {
            return response()->json(['code' => 404, 'error' => '404 not found'], 404);
        }
        $data=[];

        // APP section
        $data['APP']['name']=$app->name;
        $data['APP']['id']=$app->app_id;
        $data['APP']['customerName']=$app->customerName;

        return response()->json($data, 200);
    }
}
