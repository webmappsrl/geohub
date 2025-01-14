<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AppElbrusEditorialContentController;
use App\Http\Controllers\AppElbrusTaxonomyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassificationController;
use App\Http\Controllers\EcPoiController;
use App\Http\Controllers\EcTrackController;
use App\Http\Controllers\EditorialContentController;
use App\Http\Controllers\LayerAPIController;
use App\Http\Controllers\TaxonomyActivityController;
use App\Http\Controllers\TaxonomyPoiTypeController;
use App\Http\Controllers\TaxonomyTargetController;
use App\Http\Controllers\TaxonomyThemeController;
use App\Http\Controllers\TaxonomyWhenController;
use App\Http\Controllers\TaxonomyWhereController;
use App\Http\Controllers\UgcMediaController;
use App\Http\Controllers\UgcPoiController;
use App\Http\Controllers\UgcTrackController;
use App\Http\Controllers\UserGeneratedDataController;
use App\Http\Controllers\V1\AppAPIController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WebmappAppController;
use App\Http\Resources\TaxonomyActivityResource;
use App\Http\Resources\TaxonomyPoiTypeResource;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyWhere;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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

Route::get('downloadUserUgcMediaGeojson/{user_id}', [UgcMediaController::class, 'downloadUserGeojson'])
    ->name('downloadUserUgcMediaGeojson');

Route::name('api.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->name('login');
    Route::middleware('throttle:100,1')->post('/auth/signup', [AuthController::class, 'signup'])->name('signup');
    Route::group([
        'middleware' => 'auth.jwt',
        'prefix' => 'auth',
    ], function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::post('me', [AuthController::class, 'me'])->name('me');
        Route::post('delete', [AuthController::class, 'delete'])->name('delete');
    });

    /**
     * Here should go all the api that need authentication
     */
    Route::group([
        'middleware' => 'auth.jwt',
    ], function () {
        Route::prefix('ugc')->name('ugc.')->group(function () {
            Route::prefix('poi')->name('poi.')->group(function () {
                Route::post('store/{version?}', [UgcPoiController::class, 'store'])->name('store');
                Route::get('index/{version?}', [UgcPoiController::class, 'index'])->name('index');
                Route::get('delete/{id}', [UgcPoiController::class, 'destroy'])->name('destroy');
                Route::post('edit', [UgcPoiController::class, 'edit'])->name('edit');
            });
            Route::prefix('track')->name('track.')->group(function () {
                Route::post('store/{version?}', [UgcTrackController::class, 'store'])->name('store');
                Route::get('index/{version?}', [UgcTrackController::class, 'index'])->name('index');
                Route::get('delete/{id}', [UgcTrackController::class, 'destroy'])->name('destroy');
                Route::post('edit', [UgcTrackController::class, 'edit'])->name('edit');
            });
            Route::prefix('media')->name('media.')->group(function () {
                // TODO: riabilitare quando fixato il bug
                Route::post('store/{version?}', [UgcMediaController::class, 'store'])->name('store');
                Route::get('index/{version?}', [UgcMediaController::class, 'index'])->name('index');
                Route::get('delete/{id}', [UgcMediaController::class, 'destroy'])->name('destroy');
            });
        });
        Route::post('/userGeneratedData/store', [UserGeneratedDataController::class, 'store']);
        Route::post('/usergenerateddata/store', [UserGeneratedDataController::class, 'store']);
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::post('/buy', [WalletController::class, 'buy'])->name('buy');
        });
    });

    /**
     * Taxonomies API
     */
    Route::prefix('taxonomy')->name('taxonomy.')->group(function () {
        Route::prefix('activity')->name('activity.')->group(function () {
            Route::get('/{id}', [TaxonomyActivityController::class, 'getTaxonomyActivity'])->name('json');
            Route::get('/idt/{identifier}', [TaxonomyActivityController::class, 'getTaxonomyActivityFromIdentifier'])->name('json.idt');
        });
        Route::prefix('poi_type')->name('poi_type.')->group(function () {
            Route::get('/{id}', [TaxonomyPoiTypeController::class, 'getTaxonomyPoiType'])->name('json');
            Route::get('/idt/{identifier}', [TaxonomyPoiTypeController::class, 'getTaxonomyPoiTypeFromIdentifier'])->name('json.idt');
        });
        Route::prefix('target')->name('target.')->group(function () {
            Route::get('/{id}', [TaxonomyTargetController::class, 'getTaxonomyTarget'])->name('json');
            Route::get('/idt/{identifier}', [TaxonomyTargetController::class, 'getTaxonomyTargetFromIdentifier'])->name('json.idt');
        });
        Route::prefix('theme')->name('theme.')->group(function () {
            Route::get('/{id}', [TaxonomyThemeController::class, 'getTaxonomyTheme'])->name('json');
            Route::get('/idt/{identifier}', [TaxonomyThemeController::class, 'getTaxonomyThemeFromIdentifier'])->name('json.idt');
        });
        Route::prefix('when')->name('when.')->group(function () {
            Route::get('/{id}', [TaxonomyWhenController::class, 'getTaxonomyWhen'])->name('json');
            Route::get('/idt/{identifier}', [TaxonomyWhenController::class, 'getTaxonomyWhenFromIdentifier'])->name('json.idt');
        });
        Route::prefix('where')->name('where.')->group(function () {
            Route::get('/geojson/{id}', [TaxonomyWhereController::class, 'getGeoJsonFromTaxonomyWhere'])->name('geojson');
            Route::get('/geojson/idt/{identifier}', [TaxonomyWhereController::class, 'getGeoJsonFromTaxonomyWhereIdentifier'])->name('geojson.idt');
            Route::get('/{id}', [TaxonomyWhereController::class, 'getTaxonomyWhere'])->name('json');
            Route::get('/idt/{identifier}', [TaxonomyWhereController::class, 'getTaxonomyWhereFromIdentifier'])->name('json.idt');
        });
    });

    /**
     * Ugc API
     */
    Route::prefix('ugc')->name('ugc.')->group(function () {
        Route::prefix('poi')->name('poi.')->group(function () {
            Route::get('/geojson/{id}', [UserGeneratedDataController::class, 'getUgcGeojson'])->name('geojson');
            Route::get('/geojson/{id}/osm2cai', [UserGeneratedDataController::class, 'getUgcGeojsonOsm2cai'])->name('geojson.poi.osm2cai');
            Route::get('/geojson/{app_id}/list', [UserGeneratedDataController::class, 'getUgcList'])->name('ugc_list');
            Route::post('/taxonomy_where', [UserGeneratedDataController::class, 'associateTaxonomyWhereWithUgcFeature'])->name('associate');
        });

        Route::prefix('track')->name('track.')->group(function () {
            Route::get('/geojson/{id}', [UserGeneratedDataController::class, 'getUgcGeojson'])->name('geojson');
            Route::get('/geojson/{id}/osm2cai', [UserGeneratedDataController::class, 'getUgcGeojsonOsm2cai'])->name('geojson.track.osm2cai');
            Route::get('/geojson/{app_id}/list', [UserGeneratedDataController::class, 'getUgcList'])->name('ugc_list');
            Route::post('/taxonomy_where', [UserGeneratedDataController::class, 'associateTaxonomyWhereWithUgcFeature'])->name('associate');
        });
        Route::prefix('media')->name('media.')->group(function () {
            Route::get('/geojson/{id}', [UserGeneratedDataController::class, 'getUgcGeojson'])->name('geojson');
            Route::get('/geojson/{id}/osm2cai', [UserGeneratedDataController::class, 'getUgcGeojsonOsm2cai'])->name('geojson.media.osm2cai');
            Route::get('/geojson/{app_id}/list', [UserGeneratedDataController::class, 'getUgcList'])->name('ugc_list');
            Route::post('/taxonomy_where', [UserGeneratedDataController::class, 'associateTaxonomyWhereWithUgcFeature'])->name('associate');
            Route::get('/download/{id}', [UgcMediaController::class, 'download'])->name('download');
            Route::post('/update/{id}', [UgcMediaController::class, 'update'])->name('update');
        });
    });

    /**
     * ec API
     */
    Route::prefix('ec')->name('ec.')->group(function () {
        Route::prefix('media')->name('media.')->group(function () {
            Route::get('/image/{id}', [EditorialContentController::class, 'getEcImage'])->name('image');
            Route::get('/{id}', [EditorialContentController::class, 'viewEcGeojson'])->name('geojson');
        });
        Route::prefix('poi')->name('poi.')->group(function () {
            Route::put('/update/{id}', [EditorialContentController::class, 'updateEcPoi'])->name('update');
            Route::prefix('download')->group(function () {
                Route::get('/{id}.geojson', [EditorialContentController::class, 'downloadEcGeojson'])->name('download.geojson');
                Route::get('/{id}.gpx', [EditorialContentController::class, 'downloadEcGpx'])->name('download.gpx');
                Route::get('/{id}.kml', [EditorialContentController::class, 'downloadEcKml'])->name('download.kml');
                Route::get('/{id}', [EditorialContentController::class, 'downloadEcGeojson'])->name('download');
            });
            Route::get('/{id}/neighbour_media', [EcPoiController::class, 'getNeighbourEcMedia']);
            Route::get('/{id}/associated_ec_media', [EcPoiController::class, 'getAssociatedEcMedia']);
            Route::get('/{id}/feature_image', [EcPoiController::class, 'getFeatureImage']);
            Route::get('/{id}.geojson', [EditorialContentController::class, 'viewEcGeojson'])->name('view.geojson');
            Route::get('/{id}.gpx', [EditorialContentController::class, 'viewEcGpx'])->name('view.gpx');
            Route::get('/{id}.kml', [EditorialContentController::class, 'viewEcKml'])->name('view.kml');
            Route::get('/{id}', [EditorialContentController::class, 'viewEcGeojson'])->name('json');
        });
        Route::prefix('track')->name('track.')->group(function () {
            Route::get('/search', [EcTrackController::class, 'search'])->name('search');
            Route::get('/nearest/{lon}/{lat}', [EcTrackController::class, 'nearestToLocation'])->name('nearest_to_location');
            Route::get('/most_viewed', [EcTrackController::class, 'mostViewed'])->name('most_viewed');
            Route::get('/multiple', [EcTrackController::class, 'multiple'])->name('multiple');
            Route::get('/pdf/{id}', [EcTrackController::class, 'getFeatureCollectionForTrackPdf'])->name('feature_collection_for_pdf');
            Route::middleware('auth.jwt')
                ->prefix('favorite')->name('favorite.')->group(function () {
                    Route::post('/add/{id}', [EcTrackController::class, 'addFavorite'])->name('add');
                    Route::post('/remove/{id}', [EcTrackController::class, 'removeFavorite'])->name('remove');
                    Route::post('/toggle/{id}', [EcTrackController::class, 'toggleFavorite'])->name('toggle');
                    Route::get('/list', [EcTrackController::class, 'listFavorites'])->name('list');
                });
            Route::prefix('download')->group(function () {
                Route::get('/{id}.geojson', [EditorialContentController::class, 'downloadEcGeojson'])->name('download.geojson');
                Route::get('/{id}.gpx', [EditorialContentController::class, 'downloadEcGpx'])->name('download.gpx');
                Route::get('/{id}.kml', [EditorialContentController::class, 'downloadEcKml'])->name('download.kml');
                Route::get('/{id}', [EditorialContentController::class, 'downloadEcGeojson'])->name('download');
            });
            Route::get('/{id}/neighbour_pois', [EcTrackController::class, 'getNeighbourEcPoi']);
            Route::get('/{id}/associated_ec_pois', [EcTrackController::class, 'getAssociatedEcPois']);
            Route::get('/{id}/neighbour_media', [EcTrackController::class, 'getNeighbourEcMedia']);
            Route::get('/{id}/associated_ec_media', [EcTrackController::class, 'getAssociatedEcMedia']);
            Route::get('/{id}/feature_image', [EcTrackController::class, 'getFeatureImage']);
            Route::get('/{id}.geojson', [EcTrackController::class, 'getGeojson'])->name('view.geojson');
            Route::get('/{id}.gpx', [EditorialContentController::class, 'viewEcGpx'])->name('view.gpx');
            Route::get('/{id}.kml', [EditorialContentController::class, 'viewEcKml'])->name('view.kml');
            Route::get('/{id}', [EcTrackController::class, 'getGeojson'])->name('json');
        });
    });

    Route::post('search', [WebmappAppController::class, 'search'])->name('search');

    /**
     * APP API (/app/*)
     */
    Route::prefix('app')->name('app.')->group(function () {
        /**
         * ELBRUS API
         */
        Route::prefix('elbrus')->name('elbrus.')->group(function () {
            Route::get('/{id}/config.json', [AppController::class, 'config'])->name('config');
            Route::get('/{id}/resources/icon.png', [AppController::class, 'icon'])->name('icon');
            Route::get('/{id}/resources/splash.png', [AppController::class, 'splash'])->name('splash');
            Route::get('/{id}/resources/icon_small.png', [AppController::class, 'iconSmall'])->name('icon_small');
            Route::get('/{id}/resources/feature_image.png', [AppController::class, 'featureImage'])->name('feature_image');
            Route::get('/{app_id}/geojson/ec_poi_{poi_id}.geojson', [AppElbrusEditorialContentController::class, 'getPoiGeojson'])->name('geojson.poi');
            Route::get('/{app_id}/geojson/ec_track_{track_id}.geojson', [AppElbrusEditorialContentController::class, 'getTrackGeojson'])->name('geojson.track');
            Route::get('/{app_id}/geojson/ec_track_{track_id}.json', [AppElbrusEditorialContentController::class, 'getTrackJson'])->name('geojson.track.json');
            Route::get('/{app_id}/taxonomies/track_{taxonomy_name}_{term_id}.json', [AppElbrusTaxonomyController::class, 'getTracksByAppAndTerm'])->where([
                'app_id' => '[0-9]+',
                'taxonomy_name' => '[a-z\_]+',
                'term_id' => '[0-9]+',
            ])->name('track.taxonomies');
            Route::get('/{app_id}/taxonomies/{taxonomy_name}.json', [AppElbrusTaxonomyController::class, 'getTerms'])->name('taxonomies');
            Route::get('/{app_id}/tiles/map.mbtiles', function ($app_id) {
                return redirect('https://k.webmapp.it/elbrus/'.$app_id.'.mbtiles');
            });
        });
        Route::prefix('webmapp')->name('webmapp.')->group(function () {
            Route::get('/{id}/config.json', [AppController::class, 'config'])->name('config');
            Route::get('/{id}/base-config.json', [AppController::class, 'baseConfig'])->name('baseConfig');
            Route::get('/{id}/classification/ranked_users_near_pois.json', [ClassificationController::class, 'getRankedUsersNearPois'])->name('getRankedUsersNearPois');
            Route::get('/{id}/resources/icon.png', [AppController::class, 'icon'])->name('icon');
            Route::get('/{id}/resources/splash.png', [AppController::class, 'splash'])->name('splash');
            Route::get('/{id}/resources/icon_small.png', [AppController::class, 'iconSmall'])->name('icon_small');
            Route::get('/{id}/resources/feature_image.png', [AppController::class, 'featureImage'])->name('feature_image');
            Route::get('/{id}/resources/icon_notify.png', [AppController::class, 'iconNotify'])->name('icon_notify');
            Route::get('/{id}/resources/logo_homepage.svg', [AppController::class, 'logoHomepage'])->name('logo_homepage');
        });
        Route::prefix('webapp')->name('webapp.')->group(function () {
            Route::get('/{id}/config', [AppController::class, 'config'])->name('config');
            Route::get('/{id}/vector_style', [AppController::class, 'vectorStyle'])->name('vector_style');
            Route::get('/{id}/vector_layer', [AppController::class, 'vectorLayer'])->name('vector_layer');
            Route::get('/{id}/tracks_list', [AppController::class, 'tracksList'])->name('tracks_list');
            Route::get('/{id}/pois_list', [AppController::class, 'poisList'])->name('pois_list');
            Route::get('/{id}/layer/{layer_id}', [AppController::class, 'layer'])->name('layer');
            Route::get('/{id}/taxonomies/{taxonomy_name}/{term_id}', [AppController::class, 'getFeaturesByAppAndTerm'])->where([
                'app_id' => '[0-9]+',
                'taxonomy_name' => '[a-z\_]+',
                'term_id' => '[0-9]+',
            ])->name('feature.taxonomies');
        });
    });

    /**
     * FRONTEND API VERSION 1 (/api/v1)
     */
    Route::prefix('v1')->name('v1.')->group(function () {
        Route::prefix('app')->name('v1.app.')->group(function () {
            Route::get('/{id}/pois.geojson', [AppAPIController::class, 'pois'])->name('app_pois');
            Route::get('/all', [AppAPIController::class, 'all'])->name('apps_json');
        });
    });

    /**
     * FRONTEND API VERSION 2 (/api/v2)
     */
    Route::prefix('v2')->group(function () {
        Route::prefix('app')->group(function () {
            Route::get('/{id}/pois.geojson', [AppAPIController::class, 'pois'])->name('app_pois');
            Route::get('/all', [AppAPIController::class, 'all'])->name('apps_json');
            Route::prefix('webmapp')->name('webmapp.')->group(function () {
                Route::get('/{id}/config.json', [AppController::class, 'config'])->name('config');
                Route::get('/{id}/resources/icon.png', [AppController::class, 'icon'])->name('icon');
                Route::get('/{id}/resources/splash.png', [AppController::class, 'splash'])->name('splash');
                Route::get('/{id}/resources/icon_small.png', [AppController::class, 'iconSmall'])->name('icon_small');
                Route::get('/{id}/resources/feature_image.png', [AppController::class, 'featureImage'])->name('feature_image');
                Route::get('/{id}/resources/icon_notify.png', [AppController::class, 'iconNotify'])->name('icon_notify');
                Route::get('/{id}/resources/logo_homepage.svg', [AppController::class, 'logoHomepage'])->name('logo_homepage');
            });
        });
    });

    // Export API
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('/layers', [LayerAPIController::class, 'layers'])->name('export_layers');
        Route::get('/editors', function () {
            return User::whereHas('roles', function ($q) {
                $q->where('role_id', 2);
            })->get()->toArray();
        })->name('export_editors');
        Route::get('/admins', function () {
            return User::whereHas('roles', function ($q) {
                $q->where('role_id', 1);
            })->get()->toArray();
        })->name('export_admins');
        Route::get('/tracks/{email?}', [EcTrackController::class, 'exportTracksByAuthorEmail'])->name('exportTracksByAuthorEmail');
        Route::get('/pois/{email?}', [EcPoiController::class, 'exportPoisByAuthorEmail'])->name('exportPoisByAuthorEmail');
        Route::prefix('taxonomy')->name('taxonomy.')->group(function () {
            Route::get('/themes', [TaxonomyThemeController::class, 'exportAllThemes'])->name('export_themes');
            Route::get('/wheres', function () {
                return TaxonomyWhere::all()->pluck('updated_at', 'id')->toArray();
            })->name('export_wheres_list');
            Route::get('/activities', function () {
                return TaxonomyActivityResource::collection(TaxonomyActivity::all());
            })->name('export_activities');
            Route::get('/poi_types', function () {
                return TaxonomyPoiTypeResource::collection(TaxonomyPoiType::all());
            })->name('export_poi_types');
            Route::get('/{app}/{name}', function ($app, $name) {
                return Storage::disk('importer')->get("geojson/$app/$name");
            })->name('sardegnasentieriaree');
            Route::get('/{geojson}/{app}/{name}', function ($geojson, $app, $name) {
                return Storage::disk('public')->get("$geojson/$app/$name");
            })->name('getOverlaysPath');
        });
    });

    /**
     * OSF API
     */
    Route::prefix('osf')->name('osf.')->group(function () {
        Route::prefix('track')->name('track.')->group(function () {
            Route::get('/{endpoint_slug}/{source_id}', [EcTrackController::class, 'getTrackGeojsonFromSourceID'])->name('get_ectrack_from_source_id');
        });
        Route::prefix('poi')->name('poi.')->group(function () {
            Route::get('/{endpoint_slug}/{source_id}', [EcPoiController::class, 'getPoiGeojsonFromSourceID'])->name('get_ecpoi_from_source_id');
        });
    });

    /**
     * webapp redirect API with external ID and Slug
     */
    Route::prefix('webapp')->name('webapp.')->group(function () {
        Route::prefix('track')->name('track.')->group(function () {
            Route::get('/{endpoint_slug}/{source_id}', [EcTrackController::class, 'getEcTrackWebappURLFromSourceID'])->name('get_ectrack_webapp_url_from_source_id');
        });
        Route::prefix('poi')->name('poi.')->group(function () {
            Route::get('/{endpoint_slug}/{source_id}', [EcPoiController::class, 'getEcPoiWebappURLFromSourceID'])->name('get_ecpoi_webapp_url_from_source_id');
        });
    });
});
