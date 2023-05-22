<?php

namespace App\Console\Commands;

use App\Models\App;
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
    protected $signature = 'command:createOverlayGeojson
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
        try {
            $overlay = OverlayLayer::findOrFail($this->argument('overlay_id'));
            $layers = $overlay->layers;
        } catch (ModelNotFoundException $e) {
            $this->error('OverlayLayer with id ' . $this->argument('overlay_id') . ' not found.');
            return 1;
        }
        try {
            $app = App::findOrFail($this->argument('app_id'));
            $appId = $app->id;
        } catch (ModelNotFoundException $e) {
            $this->error('App with id ' . $this->argument('app_id') . ' not found.');
            return 1;
        }
        $fileName = $this->argument('name');
        $this->info('found ' . $layers->count() . ' layers for '  . $overlay->name . ' of app ' . $app->name);
        $this->info('Creating geojson file for overlay layer ' . $overlay->name . ' of app ' . $app->name . ' with name ' . $fileName . '.');

        $featureCollection = [];
        $featureCollection['type'] = 'FeatureCollection';

        foreach ($layers as $layer) {
            $this->info('processing layer ' . $layer->name .  '...');
            $taxonomyWheres = $layer->taxonomyWheres;
            $this->info('found ' . $taxonomyWheres->count() . ' taxonomies');
            foreach ($taxonomyWheres as $taxonomyWhere) {
                $this->info('processing taxonomyWhere ' . $taxonomyWhere->name);
                $featureCollection['features'][] = $this->createFeature($taxonomyWhere, $layer);
            }
            $this->info('saving geojson file for taxonomyWhere ' . $taxonomyWhere->name);
            $this->saveFeatureCollectionFile($featureCollection, $fileName, $appId, $overlay);
            $this->info('geojson file for taxonomyWhere ' . $taxonomyWhere->name . ' saved.');
        }
    }

    private function createFeatureCollection(TaxonomyWhere $taxonomyWhere, Layer $layer)
    {
        $featureCollection = [];
        $featureCollection['type'] = 'FeatureCollection';
        $featureCollection['features'] = [];
        $featureCollection['features'][] = $this->createFeature($taxonomyWhere, $layer);
        return $featureCollection;
    }

    private function createFeature(TaxonomyWhere $taxonomyWhere, Layer $layer)
    {
        $feature = [];
        $feature['type'] = 'Feature';
        $feature['geometry'] = [];
        $feature['geometry']['type'] = 'MultiPolygon';
        $feature['geometry']['coordinates'] = [$taxonomyWhere->geometry];
        $feature['properties'] = $this->createProperties($layer);
        return $feature;
    }

    private function createProperties(Layer $layer)
    {
        $properties = [];
        $properties['layer'] = [];
        $properties['layer']['id'] = $layer->id;
        $properties['layer']['name'] = $layer->name;
        $properties['layer']['title'] = $layer->title;
        $properties['layer']['description'] = $layer->description;
        $properties['layer']['feature_image'] = "https://ecmedia.s3.eu-central-1.amazonaws.com/EcMedia/Resize/400x200/13183_400x200.jpg";
        $properties['layer']['stats'] = [];
        $properties['layer']['stats']['tracks_count'] = 17;
        $properties['layer']['stats']['total_tracks_length'] = 123;
        $properties['layer']['stats']['poi_count'] = 97;
        return $properties;
    }

    private function saveFeatureCollectionFile(array $featureCollection, string $fileName, int $appId, OverlayLayer $overlayLayer)
    {
        //create a directory named as the app id
        $path = storage_path('app/public/geojson/' . $appId);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        //create a file named as the name argument
        $file = fopen($path . '/' . $fileName . '.geojson', 'w');
        fwrite($file, json_encode($featureCollection));
        fclose($file);
        $overlayLayer->feature_collection = $path . '/' . $fileName . '.geojson';
        $overlayLayer->save();
    }
}
