<?php

use App\Http\Controllers\EmulateUserController;
use App\Http\Controllers\ImportController;
use App\Models\EcTrack;
use Illuminate\Support\Facades\Auth;
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

Route::get('/track/{id}',function($id){
    $track = EcTrack::find($id);

    if ($track == null) {
        abort(404);
    }
    return view('track',[
        'track' => $track
    ]);

});
