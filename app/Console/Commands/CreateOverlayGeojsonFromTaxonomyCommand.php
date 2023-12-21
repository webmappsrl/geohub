<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\Layer;
use App\Models\OverlayLayer;
use App\Models\TaxonomyWhere;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class CreateOverlayGeojsonFromTaxonomyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:createOverlayGeojson
                            {app_id : ID of the App} 
                            {overlay_id : ID of the interactive overlay layer} 
                            {name : the name of the generated file} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command creates a featureCollection file that has all the geometries of the selected taxonomyWheres and puts correlated layer information in the properties of each feature.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //handle the case where the overlayLayer id provided is not valid
        try {
            $overlayLayer = OverlayLayer::findOrFail($this->argument('overlay_id'));
            $layers = $overlayLayer->layers;
        } catch (ModelNotFoundException $e) {
            $this->error('OverlayLayer with id '.$this->argument('overlay_id').' not found.');

            return 1;
        }
        //handle the case where the app id provided is not valid
        try {
            $app = App::findOrFail($this->argument('app_id'));
            $appId = $app->id;
        } catch (ModelNotFoundException $e) {
            $this->error('App with id '.$this->argument('app_id').' not found.');

            return 1;
        }
        //get the file name from the command input
        $fileName = $this->argument('name');

        //if no layers are found, abort
        if ($layers->count() == 0) {
            $this->error('No layers found for overlay layer '.$overlayLayer->name);

            return 1;
        }

        $this->info('found '.$layers->count().' layers for '.$overlayLayer->name);

        $featureCollection = [];
        $featureCollection['type'] = 'FeatureCollection';

        foreach ($layers as $layer) {
            $this->info('processing layer '.$layer->name.'...');

            $taxonomyWheres = $layer->taxonomyWheres;

            //if no taxonomyWheres are found,print error and skip to the next layer
            if ($taxonomyWheres->count() == 0) {
                $this->error('No taxonomies found for layer '.$layer->name);

                continue;
            } else {
                $this->info('FOUND '.$taxonomyWheres->count().' TAXONOMIES FOR LAYER '.$layer->name.'...');
                foreach ($taxonomyWheres as $taxonomyWhere) {
                    $this->info('processing taxonomyWhere '.$taxonomyWhere->name);
                    $featureCollection['features'][] = $this->createFeature($taxonomyWhere, $layer);
                    $this->info('taxonomyWhere '.$taxonomyWhere->name.' processed. Feature created successfully.');
                }
            }
        }
        $this->saveFeatureCollectionFile($featureCollection, $fileName, $appId, $overlayLayer);
        $this->info('The file has been created successfully and it is located at storage/app/public/'.$overlayLayer->feature_collection);
    }

    private function createFeature(TaxonomyWhere $taxonomyWhere, Layer $layer): array
    {
        //get the geojson of the taxonomyWhere
        $query = 'SELECT ST_AsGeoJSON(geometry) as geometry FROM taxonomy_wheres WHERE id = '.$taxonomyWhere->id;
        $geometry = DB::select($query)[0]->geometry;

        //create the feature
        $feature = [];
        $feature['type'] = 'Feature';
        $feature['geometry'] = [];
        $feature['geometry'] = json_decode($geometry);
        $feature['properties'] = $this->createProperties($taxonomyWhere, $layer);

        return $feature;
    }

    private function createProperties(TaxonomyWhere $taxonomyWhere, Layer $layer): array
    {
        //thumbnail 400x200
        $ecMedia = EcMedia::find($taxonomyWhere->feature_image);
        $featureImageLink = $ecMedia ? $ecMedia->thumbnail('400x200') : '';
        $tracksCount = EcTrack::where('user_id', $taxonomyWhere->user_id)->count() ?? '';
        $totalTracksLength = $taxonomyWhere->user_id ? $this->getTotalTracksLength($taxonomyWhere->user_id) : '';
        $poisCount = EcPoi::where('user_id', $taxonomyWhere->user_id)->count() ?? '';
        $title = $layer->getTranslations('title');
        $taxonomyWhereDescription = $taxonomyWhere->getTranslations('description');
        $layerDescription = $layer->getTranslations('description');

        $properties = [];
        $properties['layer'] = [];
        $properties['layer']['id'] = $layer->id;
        $properties['layer']['name'] = $layer->name ?? '';
        $properties['layer']['title'] = $title ?? '';
        if (! empty($layerDescription) || ! empty($taxonomyWhereDescription)) {
            $properties['layer']['description'] = ! empty($layerDescription) ? $layerDescription : $taxonomyWhereDescription;
        }
        $properties['layer']['feature_image'] = $featureImageLink ?? '';
        if ($layer->taxonomyWheres->count() > 0) {
            $properties['layer']['taxonomy_wheres'] = [];
            foreach ($layer->taxonomyWheres as $tax) {
                array_push($properties['layer']['taxonomy_wheres'], [
                    'id' => $tax->id,
                    'name' => $tax->getTranslations('name'),
                    'identifier' => $tax->identifier,
                ]);
            }
        }
        $properties['layer']['stats'] = [];
        $properties['layer']['stats']['tracks_count'] = $tracksCount;
        $properties['layer']['stats']['total_tracks_length'] = $totalTracksLength;
        $properties['layer']['stats']['poi_count'] = $poisCount;

        return $properties;
    }

    private function saveFeatureCollectionFile(array $featureCollection, string $fileName, int $appId, OverlayLayer $overlayLayer): void
    {
        //create a directory named as the app id
        $path = storage_path('app/public/geojson/'.$appId);
        if (! file_exists($path)) {
            mkdir($path, 0777, true);
        }
        //create a file named as the name argument
        $file = fopen($path.'/'.$fileName.'.geojson', 'w');
        fwrite($file, json_encode($featureCollection, true));
        fclose($file);
        $overlayLayer->feature_collection = 'geojson'.'/'.$appId.'/'.$fileName.'.geojson';
        $overlayLayer->save();
    }

    private function getTotalTracksLength(int $userId): float
    {
        $tracks = EcTrack::where('user_id', $userId)->get();
        $totalLength = 0;
        foreach ($tracks as $track) {
            $totalLength += $track->distance;
        }

        return $totalLength;
    }
}
