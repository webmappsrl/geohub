<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EditorialContentController;
use App\Http\Controllers\TaxonomyActivityController;
use App\Http\Controllers\TaxonomyPoiTypeController;
use App\Http\Controllers\TaxonomyTargetController;
use App\Http\Controllers\TaxonomyThemeController;
use App\Http\Controllers\TaxonomyWhenController;
use App\Http\Controllers\TaxonomyWhereController;
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
        Route::prefix('activity')->name('activity.')->group(function () {
            Route::get("/{id}", [TaxonomyActivityController::class, 'getTaxonomyActivity'])->name('json');
            Route::get("/idt/{identifier}", [TaxonomyActivityController::class, 'getTaxonomyActivityFromIdentifier'])->name('json.idt');
        });
        Route::prefix('poi_type')->name('poi_type.')->group(function () {
            Route::get("/{id}", [TaxonomyPoiTypeController::class, 'getTaxonomyPoiType'])->name('json');
            Route::get("/idt/{identifier}", [TaxonomyPoiTypeController::class, 'getTaxonomyPoiTypeFromIdentifier'])->name('json.idt');
        });
        Route::prefix('target')->name('target.')->group(function () {
            Route::get("/{id}", [TaxonomyTargetController::class, 'getTaxonomyTarget'])->name('json');
            Route::get("/idt/{identifier}", [TaxonomyTargetController::class, 'getTaxonomyTargetFromIdentifier'])->name('json.idt');
        });
        Route::prefix('theme')->name('theme.')->group(function () {
            Route::get("/{id}", [TaxonomyThemeController::class, 'getTaxonomyTheme'])->name('json');
            Route::get("/idt/{identifier}", [TaxonomyThemeController::class, 'getTaxonomyThemeFromIdentifier'])->name('json.idt');
        });
        Route::prefix('when')->name('when.')->group(function () {
            Route::get("/{id}", [TaxonomyWhenController::class, 'getTaxonomyWhen'])->name('json');
            Route::get("/idt/{identifier}", [TaxonomyWhenController::class, 'getTaxonomyWhenFromIdentifier'])->name('json.idt');
        });
        Route::prefix('where')->name('where.')->group(function () {
            Route::get("/geojson/{id}", [TaxonomyWhereController::class, 'getGeoJsonFromTaxonomyWhere'])->name('geojson');
            Route::get("/geojson/idt/{identifier}", [TaxonomyWhereController::class, 'getGeoJsonFromTaxonomyWhereIdentifier'])->name('geojson.idt');
        });
    });
    Route::prefix('ugc')->name('ugc.')->group(function () {
        Route::prefix('poi')->name('poi.')->group(function () {
            Route::get("/geojson/{id}", [UserGeneratedDataController::class, 'getUgcGeojson'])->name('geojson');
            Route::post("/taxonomy_where", [UserGeneratedDataController::class, 'associateTaxonomyWhereWithUgcFeature'])->name('associate');
        });
        Route::prefix('track')->name('track.')->group(function () {
            Route::get("/geojson/{id}", [UserGeneratedDataController::class, 'getUgcGeojson'])->name('geojson');
            Route::post("/taxonomy_where", [UserGeneratedDataController::class, 'associateTaxonomyWhereWithUgcFeature'])->name('associate');
        });
        Route::prefix('media')->name('media.')->group(function () {
            Route::get("/geojson/{id}", [UserGeneratedDataController::class, 'getUgcGeojson'])->name('geojson');
            Route::post("/taxonomy_where", [UserGeneratedDataController::class, 'associateTaxonomyWhereWithUgcFeature'])->name('associate');
        });
    });
    Route::prefix('ec')->name('ec.')->group(function () {
        Route::prefix('media')->name('media.')->group(function () {
            Route::get("/{id}", [EditorialContentController::class, 'getEcjson'])->name('geojson');
            Route::get("/image/{id}", [EditorialContentController::class, 'getEcImage'])->name('image');
            Route::put("/update/{id}", [EditorialContentController::class, 'updateEcMedia'])->name('update');
        });
        Route::prefix('poi')->name('poi.')->group(function () {
            Route::get("/{id}", [EditorialContentController::class, 'getEcGeoJson'])->name('geojson');
            Route::put("/update/{id}", [EditorialContentController::class, 'updateEcPoi'])->name('update');
        });
        Route::prefix('track')->name('track.')->group(function () {
            Route::get("/{id}", [EditorialContentController::class, 'getEcGeoJson'])->name('geojson');
            Route::put("/update/{id}", [EditorialContentController::class, 'updateEcTrack'])->name('update');
        });
    });
});
