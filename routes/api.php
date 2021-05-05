<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaxonomyWhereController;
use App\Http\Controllers\UgcMediaController;
use App\Http\Controllers\UgcPoiController;
use App\Http\Controllers\UgcTrackController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserGeneratedDataController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::name('api.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::group([
        'middleware' => 'auth.jwt',
        'prefix' => 'auth'
    ], function ($router) {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('me', [AuthController::class, 'me']);
    });

    /**
     * Here should go all the api that need authentication
     */
    Route::group([
        'middleware' => 'auth.jwt',
    ], function ($router) {
        Route::post('/userGeneratedData/store', [UserGeneratedDataController::class, 'store']);
        Route::post('/usergenerateddata/store', [UserGeneratedDataController::class, 'store']);
    });

    Route::prefix('taxonomy')->name('taxonomy.')->group(function () {
        Route::prefix('where')->name('where.')->group(function () {
            Route::get("/geojson/{id}", [TaxonomyWhereController::class, 'getGeoJsonFromTaxonomyWhere'])->name('geojson');
        });
    });
    Route::prefix('ugc')->name('ugc.')->group(function () {
        Route::prefix('media')->name('media.')->group(function () {
            Route::get("/geojson/{id}", [UgcMediaController::class, 'getGeoJsonFromUgcMedia'])->name('geojson');
        });
        Route::prefix('poi')->name('poi.')->group(function () {
            Route::get("/geojson/{id}", [UgcPoiController::class, 'getGeoJsonFromUgcPoi'])->name('geojson');
        });
        Route::prefix('track')->name('track.')->group(function () {
            Route::get("/geojson/{id}", [UgcTrackController::class, 'getGeoJsonFromUgcTrack'])->name('geojson');
        });
    });
});
