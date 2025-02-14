<?php

namespace App\Http\Controllers;

use App\Http\Resources\UgcTrackCollection;
use App\Models\App;
use App\Models\UgcMedia;
use App\Models\UgcTrack;
use App\Providers\HoquServiceProvider;
use App\Traits\UGCFeatureCollectionTrait;
use App\Traits\ValidationTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UgcTrackController extends Controller
{
    use UGCFeatureCollectionTrait, ValidationTrait;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, $version = 'v1')
    {
        $user = auth('api')->user();
        if (isset($user)) {

            if (! empty($request->header('app-id'))) {
                $appId = $request->header('app-id');
                if (is_numeric($appId)) {
                    $app = App::where('id', $appId)->first();
                } else {
                    $app = App::where('sku', $appId)->first();
                }
                $tracks = UgcTrack::where([
                    ['user_id', $user->id],
                    ['app_id', $app->id],
                ])->orderByRaw('updated_at DESC')->get();

                return $this->getUGCFeatureCollection($tracks, $version);
            }

            $tracks = UgcTrack::where('user_id', $user->id)->orderByRaw('updated_at DESC')->get();

            return $this->getUGCFeatureCollection($tracks, $version);
        } else {
            return new UgcTrackCollection(UgcTrack::currentUser()->paginate(10));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $version = 'v1'): Response
    {
        Log::channel('ugc')->info('*************store ugc track*****************');
        Log::channel('ugc')->info('version:' . $version);
        switch ($version) {
            case 'v1':
            default:
                return $this->storeV1($request);
            case 'v2':
                return $this->storeV2($request);
        }
    }

    public function storeV1(Request $request): Response
    {
        $data = $request->all();
        Log::channel('ugc')->info('*************store v1 ugc track*****************');
        $dataProperties = $data['properties'];
        Log::channel('ugc')->info('ugc poi store properties name:' . $dataProperties['name']);
        Log::channel('ugc')->info('ugc poi store properties app_id(sku):' . $dataProperties['app_id']);
        $this->checkValidation($data, [
            'type' => 'required',
            'properties' => 'required|array',
            'properties.name' => 'required|max:255',
            'properties.app_id' => 'required|max:255',
            'geometry' => 'required|array',
            'geometry.type' => 'required',
            'geometry.coordinates' => 'required|array',
        ]);

        $user = auth('api')->user();
        if (is_null($user)) {
            return response(['error' => 'User not authenticated'], 403);
        }

        $track = new UgcTrack;
        Log::channel('ugc')->info('user email:' . $user->email);
        Log::channel('ugc')->info('user id:' . $user->id);
        $track->name = $data['properties']['name'];
        if (isset($data['properties']['description'])) {
            $track->description = $data['properties']['description'];
        }
        $track->geometry = DB::raw("ST_Force3D(ST_GeomFromGeojson('" . json_encode($data['geometry']) . "'))");
        $track->user_id = $user->id;

        if (isset($data['properties']['app_id'])) {
            $app_id = $data['properties']['app_id'];
            if (is_numeric($app_id)) {
                Log::channel('ugc')->info('numeric');
                $app = App::where('id', '=', $app_id)->first();
                if ($app != null) {
                    $track->app_id = $app_id;
                    $track->sku = $app->sku;
                }
            } else {
                Log::channel('ugc')->info('sku');
                $app = App::where('sku', '=', $app_id)->first();
                if ($app != null) {
                    $track->app_id = $app->id;
                    $track->sku = $app_id;
                }
            }
        }
        if (isset($data['properties']['metadata'])) {
            $track->metadata = json_encode(json_decode(json_encode($data['properties']['metadata'])), JSON_PRETTY_PRINT);
            unset($data['properties']['metadata']);
        }

        $track->raw_data = json_encode($data['properties']);
        try {
            $track->save();
        } catch (\Exception $e) {
            Log::channel('ugc')->info('Errore nel salvataggio della track:' . $e->getMessage());

            return response(['error' => 'Error saving Track'], 500);
        }

        if (isset($data['properties']['image_gallery']) && is_array($data['properties']['image_gallery']) && count($data['properties']['image_gallery']) > 0) {
            foreach ($data['properties']['image_gallery'] as $imageId) {
                if ((bool) UgcMedia::find($imageId)) {
                    $track->ugc_media()->attach($imageId);
                }
            }
        }

        unset($data['properties']['image_gallery']);
        $track->raw_data = json_encode($data['properties']);
        $track->save();
        $track->populateProperties();
        $track->populatePropertyForm('track_acquisition_form');

        $hoquService = app(HoquServiceProvider::class);
        try {
            $hoquService->store('update_ugc_taxonomy_wheres', ['id' => $track->id, 'type' => 'track']);
        } catch (\Exception $e) {
        }

        return response(['id' => $track->id, 'message' => 'Created successfully'], 201);
    }

    public function storeV2(Request $request): Response
    {
        $user = auth('api')->user();
        $data = $request->all();
        $feature = json_decode($data['feature'], true);
        $images = isset($data['images']) ? $data['images'] : [];
        Log::channel('ugc')->info('*************store v2 ugc track*****************');
        Log::channel('ugc')->info('user email:' . $user->email);
        Log::channel('ugc')->info('user id:' . $user->id);
        $properties = $feature['properties'];
        Log::channel('ugc')->info('ugc poi store properties name:' . $properties['name']);
        Log::channel('ugc')->info('ugc poi store properties app_id:' . $properties['app_id']);
        $this->checkValidation($data, [
            'type' => 'required',
            'properties' => 'required|array',
            'properties.name' => 'required|max:255',
            'properties.app_id' => 'required|max:255',
            'geometry' => 'required|array',
            'geometry.type' => 'required',
            'geometry.coordinates' => 'required|array',
        ]);

        $user = auth('api')->user();
        if (is_null($user)) {
            return response(['error' => 'User not authenticated'], 403);
        }

        $track = new UgcTrack;

        $track->name = $properties['name'];
        $track->geometry = DB::raw("ST_Force3D(ST_GeomFromGeojson('" . json_encode($feature['geometry']) . "'))");
        $track->properties = $properties;
        $track->user_id = $user->id;

        if (isset($properties['app_id'])) {
            $app_id = $properties['app_id'];
            if (is_numeric($app_id)) {
                Log::channel('ugc')->info('numeric');
                $app = App::where('id', '=', $app_id)->first();
                if ($app != null) {
                    $track->app_id = $app_id;
                    $track->sku = $app->sku;
                }
            } else {
                Log::channel('ugc')->info('sku');
                $app = App::where('sku', '=', $app_id)->first();
                if ($app != null) {
                    $track->app_id = $app->id;
                    $track->sku = $app_id;
                }
            }
        }

        try {
            $track->save();
        } catch (\Exception $e) {
            Log::channel('ugc')->info('Errore nel salvataggio della track:' . $e->getMessage());

            return response(['error' => 'Error saving Track'], 500);
        }
        $ugcMediaCtrl = app(UgcMediaController::class);
        $ugcMediaCtrl->saveAndAttachMediaToModel($track, $user, $images);
        $track->save();

        return response(['id' => $track->id, 'message' => 'Created successfully'], 201);
    }

    /**
     * Display the specified resource.
     *
     *
     * @return Response
     */
    public function show(UgcTrack $ugcTrack)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UgcTrack  $ugcTrack
     */
    public function edit(Request $request): Response
    {
        Log::channel('ugc')->info('*************edit ugc track*****************');

        $data = $request->all();

        if (! isset($data['properties']['id'])) {
            Log::channel('ugc')->info('ID non presente nelle properties del GeoJSON');

            return response(['error' => 'ID is required in properties'], 400);
        }

        $id = $data['properties']['id'];
        $properties = $data['properties'];

        Log::channel('ugc')->info('Modifica della track con ID: ' . $id);

        // Validazione dei dati
        $this->checkValidation($data, [
            'type' => 'required',
            'properties' => 'required|array',
            'properties.name' => 'required|max:255',
            'properties.app_id' => 'required|max:10',
            'properties.id' => 'required',
            'geometry' => 'required|array',
            'geometry.type' => 'required',
            'geometry.coordinates' => 'required|array',
        ]);

        $user = auth('api')->user();
        if (is_null($user)) {
            Log::channel('ugc')->info('Utente non autenticato');

            return response(['error' => 'User not authenticated'], 403);
        }

        // Recupero della track esistente
        $ugcTrack = UgcTrack::find($id);
        if (! $ugcTrack || $ugcTrack->user_id !== $user->id) {
            Log::channel('ugc')->info('Track non trovata o accesso non autorizzato');

            return response(['error' => 'Track not found or unauthorized access'], 404);
        }

        Log::channel('ugc')->info('user email:' . $user->email);
        Log::channel('ugc')->info('user id:' . $user->id);

        // Aggiornamento dei campi principali
        $ugcTrack->name = $properties['name'] ?? $ugcTrack->name;
        if (isset($properties['description'])) {
            $ugcTrack->description = $properties['description'];
        }
        $ugcTrack->geometry = DB::raw("ST_Force3D(ST_GeomFromGeojson('" . json_encode($data['geometry']) . "'))");
        $ugcTrack->properties = $properties;
        // Salvataggio della track
        try {
            $ugcTrack->save();
        } catch (Exception $e) {
            Log::channel('ugc')->info('Errore nel salvataggio della track: ' . $e->getMessage());

            return response(['error' => 'Error updating Track'], 500);
        }

        // Gestione image_gallery
        if (isset($properties['image_gallery']) && is_array($properties['image_gallery'])) {
            $ugcTrack->ugc_media()->sync($properties['image_gallery']);
        }

        return response(['id' => $ugcTrack->id, 'message' => 'Updated successfully'], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     *
     * @return Response
     */
    public function update(Request $request, UgcTrack $ugcTrack)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UgcTrack  $ugcTrack
     * @return Response
     */
    public function destroy($id)
    {
        try {
            $track = UgcTrack::find($id);
            $track->delete();
        } catch (Exception $e) {
            return response()->json([
                'error' => "this track can't be deleted by api",
                'code' => 400,
            ], 400);
        }

        return response()->json(['success' => 'track deleted']);
    }
}
