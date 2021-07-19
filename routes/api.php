<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EcTrackController;
use App\Http\Controllers\EditorialContentController;
use App\Http\Controllers\TaxonomyActivityController;
use App\Http\Controllers\TaxonomyPoiTypeController;
use App\Http\Controllers\TaxonomyTargetController;
use App\Http\Controllers\TaxonomyThemeController;
use App\Http\Controllers\TaxonomyWhenController;
use App\Http\Controllers\TaxonomyWhereController;
use App\Http\Controllers\ApiElbrusTaxonomyController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\EcPoiController;
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
    Route::middleware('throttle:100,1')->post('/auth/signup', [AuthController::class, 'signup']);
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

    /**
     * Taxonomies API
     */
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
            Route::get("/{id}", [TaxonomyWhereController::class, 'getTaxonomyWhere'])->name('json');
            Route::get("/idt/{identifier}", [TaxonomyWhereController::class, 'getTaxonomyWhereFromIdentifier'])->name('json.idt');
        });
    });

    /**
     * Ugc API
     */
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

    /**
     * ec API
     */
    Route::prefix('ec')->name('ec.')->group(function () {
        Route::prefix('media')->name('media.')->group(function () {
            Route::get("/{id}", [EditorialContentController::class, 'getEcjson'])->name('geojson');
            Route::get("/image/{id}", [EditorialContentController::class, 'getEcImage'])->name('image');
            Route::put("/update/{id}", [EditorialContentController::class, 'updateEcMedia'])->name('update');
        });
        Route::prefix('poi')->name('poi.')->group(function () {
            Route::get("/{id}/near_points", [EcPoiController::class, 'getNearEcMedia']);
            Route::get("/{id}/associated_ec_media", [EcPoiController::class, 'getAssociatedEcMedia']);
            Route::get("/{id}.geojson", [EditorialContentController::class, 'viewEcGeojson'])->name('view.geojson');
            Route::get("/{id}.gpx", [EditorialContentController::class, 'viewEcGpx'])->name('view.gpx');
            Route::get("/{id}.kml", [EditorialContentController::class, 'viewEcKml'])->name('view.kml');
            Route::get("/{id}", [EditorialContentController::class, 'getEcJson'])->name('json');
            Route::put("/update/{id}", [EditorialContentController::class, 'updateEcPoi'])->name('update');
            Route::prefix('download')->group(function () {
                Route::get("/{id}.geojson", [EditorialContentController::class, 'downloadEcGeojson'])->name('download.geojson');
                Route::get("/{id}.gpx", [EditorialContentController::class, 'downloadEcGpx'])->name('download.gpx');
                Route::get("/{id}.kml", [EditorialContentController::class, 'downloadEcKml'])->name('download.kml');
                Route::get("/{id}", [EditorialContentController::class, 'downloadEcGeojson'])->name('download.geojson');
            });
        });
        Route::prefix('track')->name('track.')->group(function () {
            Route::get("/{id}/near_points", [EcTrackController::class, 'getNearEcMedia']);
            Route::get("/{id}/associated_ec_media", [EcTrackController::class, 'getAssociatedEcMedia']);
            Route::get("/{id}.geojson", [EditorialContentController::class, 'getEcJson'])->name('view.geojson');
            Route::get("/{id}.gpx", [EditorialContentController::class, 'viewEcGpx'])->name('view.gpx');
            Route::get("/{id}.kml", [EditorialContentController::class, 'viewEcKml'])->name('view.kml');
            Route::get("/{id}", [EditorialContentController::class, 'getEcJson'])->name('json');
            Route::put("/update/{id}", [EditorialContentController::class, 'updateEcTrack'])->name('update');
            Route::prefix('download')->group(function () {
                Route::get("/{id}.geojson", [EditorialContentController::class, 'downloadEcGeojson'])->name('download.geojson');
                Route::get("/{id}.gpx", [EditorialContentController::class, 'downloadEcGpx'])->name('download.gpx');
                Route::get("/{id}.kml", [EditorialContentController::class, 'downloadEcKml'])->name('download.kml');
                Route::get("/{id}", [EditorialContentController::class, 'downloadEcGeojson'])->name('download.geojson');
            });
        });
    });

    /**
     * APP API (/app/*)
     */
    Route::prefix('app')->name('app.')->group(function () {
        /**
         * APP ELBRUS API (/api/app/elbrus/*)
         * app/elbrus/{id}/config.json
         * app/elbrus/{app_id}/geojson/ec_poi_{poi_id}.geojson
         * app/elbrus/{app_id}/geojson/ec_track_{track_id}.geojson
         * app/elbrus/{app_id}/geojson/ec_track_{track_id}.json
         * app/elbrus/{app_id}/{taxonomy_name}.json
         */
        Route::prefix('elbrus')->name('elbrus.')->group(function () {
            Route::get("/{id}/config.json", [AppController::class, 'config'])->name('config');
            Route::get("/{app_id}/geojson/ec_poi_{poi_id}.geojson", [EditorialContentController::class, 'getElbrusPoiGeojson'])->name('geojson/ec_poi');
            Route::get("/{app_id}/geojson/ec_track_{track_id}.geojson", [EditorialContentController::class, 'getElbrusTrackGeojson'])->name('geojson/ec_track');
            Route::get("/{app_id}/geojson/ec_track_{track_id}.json", [EditorialContentController::class, 'getElbrusTrackJson'])->name('geojson/ec_track/json');
            Route::get("/{app_id}/taxonomies/track_{taxonomy_name}_{term_id}.json", [ApiElbrusTaxonomyController::class, 'getTracksByAppAndTerm'])->name('track.taxonomies');
            Route::get("/{app_id}/taxonomies/{taxonomy_name}.json", [ApiElbrusTaxonomyController::class, 'getTerms'])->name('taxonomies');
        });
    });
});
