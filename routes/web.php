<?php

use App\Http\Controllers\EmulateUserController;
use App\Http\Controllers\ImportController;
use App\Models\EcPoi;
use App\Models\EcTrack;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/')->name('home');

Route::prefix('/emulatedUser')->name('emulatedUser.')->group(function () {
    Route::get('/restore', [EmulateUserController::class, 'restore'])->name('restore');
});
Route::post('import/geojson', [ImportController::class, 'importGeojson'])->name('import');
Route::post('import/confirm', [ImportController::class, 'saveImport'])->name('save-import');


Route::get('language/{locale}', function ($locale) {
    app()->setLocale($locale);
    session()->put('locale', $locale);
    return redirect()->back();
});

Route::get('/track/{id}', function ($id) {
    $track = EcTrack::find($id);
    if ($track == null) {
        abort(404);
    }
    return view('track', [
        'track' => $track
    ]);
});

Route::get('/track/pdf/{id}', function ($id) {
    $track = EcTrack::find($id);
    if ($track == null) {
        abort(404);
    }
    return view('track-pdf', [
        'track' => $track
    ]);
})->name('track.pdf');

Route::get('/osf/{endpoint_slug}/{source_id}', function ($endpoint_slug, $source_id) {
    $osf_id = collect(DB::select("SELECT id FROM out_source_features where endpoint_slug='$endpoint_slug' and source_id='$source_id'"))->pluck('id')->toArray();

    $ectrack_id = collect(DB::select("select id from ec_tracks where out_source_feature_id='$osf_id[0]'"))->pluck('id')->toArray();

    $track = EcTrack::find($ectrack_id[0]);

    if ($track == null) {
        abort(404);
    }
    return view('track', [
        'track' => $track
    ]);
});

Route::get('/w/{type}/{id}', function ($type, $id) {
    $track = EcTrack::find($id);
    if ($track == null) {
        abort(404);
    }
    return view('widget', [
        'resource' => $track,
        'type' => 'track.' . $type
    ]);
});

Route::get('/poi/{type}/{id}', function ($type, $id) {
    $poi = EcPoi::find($id);
    if ($poi == null) {
        abort(404);
    }
    return view('widget', [
        'resource' => $poi,
        'type' => 'poi.' . $type
    ]);
});

Route::get('/w/osf/{type}/{endpoint_slug}/{source_id}', function ($type, $endpoint_slug, $source_id) {
    $osf = collect(DB::select("SELECT * FROM out_source_features where endpoint_slug='$endpoint_slug' and source_id='$source_id'"))->toArray();

    if (empty($osf)) {
        abort(404);
    }

    $osf_id = $osf[0]->id;
    $osf_type = $osf[0]->type;

    if ($osf_type == 'track') {
        $resource_id = collect(DB::select("select id from ec_tracks where out_source_feature_id='$osf_id'"))->pluck('id')->toArray();
        $resource = EcTrack::find($resource_id[0]);
    }

    if ($osf_type == 'poi') {
        $resource_id = collect(DB::select("select id from ec_pois where out_source_feature_id='$osf_id'"))->pluck('id')->toArray();
        $resource = EcPoi::find($resource_id[0]);
    }

    if ($resource == null) {
        abort(404);
    }
    return view('widget', [
        'resource' => $resource,
        'type' => $osf_type . '.' . $type,
    ]);
});
