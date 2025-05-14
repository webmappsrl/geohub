<?php

namespace App\Console\Commands;

use App\Models\EcMedia;
use App\Models\EcTrack;
use App\Models\Layer;
use App\Models\TaxonomyTheme;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportWpLayersCommand extends Command
{
    protected $author_id;

    protected $signature = 'geohub:import_wp_layers_command
                            {routeUrlApi : Set the url of api route)} 
                            {author : Set the author that must be assigned to EcFeature created, use email or ID } 
                            {app_id : Set the app_id to be assigned to the new Layer}
                            {--generate_edges : Set the generate_edges flag on the Layer}';

    protected $description = 'Creates the self guided layers for the given type and author.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $routeUrlApi = $this->argument('routeUrlApi');
        $authorId = $this->argument('author');
        $allTracks = EcTrack::query()->where('user_id', $authorId)->with('outSourceTrack')->get();
        $appId = $this->argument('app_id');
        $generateEdges = $this->option('generate_edges');

        $this->logAndInfo("Starting command with endpoint: $routeUrlApi and author ID: $authorId");

        try {
            $this->logAndInfo('Calling API...');

            $layerCreatedCount = 0;
            $themeCreatedCount = 0;
            $trackUpdatedCount = 0;
            $trackNotFoundCount = 0;

            $response = file_get_contents($routeUrlApi);
            $layers = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error parsing JSON response');
            }

            if (! is_array($layers)) {
                throw new Exception('API response is not a valid array');
            }

            $this->logAndInfo('API successfully loaded. Starting processing...');

            foreach ($layers as $index => $layer) {
                $this->logAndInfo("Processing layer $index");

                $layerNameRaw = $layer['title']['rendered'] ?? 'no_name';
                $layerName = html_entity_decode($layerNameRaw);
                $relatedTracks = $layer['n7webmap_route_related_track'] ?? [];

                if (count($relatedTracks) === 0) {
                    $msg = "Layer $index skipped (missing associated tracks)";
                    $this->logAndInfo($msg, 'warn');

                    continue;
                }

                $this->logAndInfo("Layer name: $layerName");
                $this->logAndInfo('Associated tracks: '.count($relatedTracks));

                $identifier = $this->slugify($layerName);
                $taxonomyTheme = TaxonomyTheme::where('identifier', $identifier)->first();

                if ($taxonomyTheme) {
                    $this->logAndInfo("Existing TaxonomyTheme found with ID: {$taxonomyTheme->id}");
                } else {
                    $taxonomyTheme = new TaxonomyTheme;
                    $taxonomyTheme->name = $layerName;
                    $taxonomyTheme->identifier = $identifier;
                    $taxonomyTheme->user_id = 1;
                    $taxonomyTheme->saveQuietly();
                    $themeCreatedCount++;
                    $this->logAndInfo("TaxonomyTheme created with ID: {$taxonomyTheme->id}");
                }

                foreach ($relatedTracks as $track) {
                    $trackNameRaw = $track['post_title'] ?? null;
                    $trackName = html_entity_decode($trackNameRaw);
                    $trackId = $track['ID'] ?? null;

                    $geohubTrack = $allTracks->first(function ($t) use ($trackId) {
                        return $t->outSourceTrack->source_id == $trackId;
                    });

                    if ($geohubTrack) {
                        $themes = is_string($geohubTrack->themes)
                            ? json_decode($geohubTrack->themes, true)
                            : ($geohubTrack->themes ?? []);
                        $themes[$taxonomyTheme->id] = [$this->slugify($taxonomyTheme->name)];
                        $geohubTrack->themes = json_encode($themes);
                        $geohubTrack->saveQuietly();
                        DB::table('taxonomy_themeables')->insertOrIgnore([
                            'taxonomy_theme_id' => $taxonomyTheme->id,
                            'taxonomy_themeable_id' => $geohubTrack->id,
                            'taxonomy_themeable_type' => EcTrack::class,
                        ]);
                        $trackUpdatedCount++;
                        $this->logAndInfo("  ↪ Track updated: {$geohubTrack->name} (ID: {$geohubTrack->id})");
                    } else {
                        $trackNotFoundCount++;
                        $msg = "Track not found: $trackName";
                        $this->logAndInfo($msg, 'warn');
                    }
                }

                $geohubLayer = new Layer;
                $geohubLayer->app_id = $appId;
                $geohubLayer->name = $layerName;
                $geohubLayer->generate_edges = $generateEdges;

                $rawDescription = $layer['content']['rendered'] ?? '';
                $cleanDescription = preg_replace('/[\r\n\t]+/', '', $rawDescription);
                $geohubLayer->description = $cleanDescription;

                $featuredImageUrl = $layer['yoast_head_json']['og_image'][0]['url'] ?? null;
                $imageCaption = $layer['yoast_head_json']['og_image'][0]['caption'] ?? null;

                if ($featuredImageUrl) {
                    $ecMedia = new EcMedia;
                    $ecMedia->name = basename(parse_url($featuredImageUrl, PHP_URL_PATH));
                    $ecMedia->description = $imageCaption ?? null;
                    $ecMedia->source = $routeUrlApi;
                    $ecMedia->user_id = $authorId;
                    $ecMedia->url = $featuredImageUrl;
                    $ecMedia->saveQuietly();

                    $geohubLayer->feature_image = $ecMedia->id;
                    $this->logAndInfo("Media created with ID: {$ecMedia->id}, file: {$ecMedia->name}");
                }

                $geohubLayer->save();
                $layerCreatedCount++;
                $this->logAndInfo("Layer created with ID: {$geohubLayer->id}");

                DB::table('taxonomy_themeables')->insert([
                    'taxonomy_theme_id' => $taxonomyTheme->id,
                    'taxonomy_themeable_id' => $geohubLayer->id,
                    'taxonomy_themeable_type' => Layer::class,
                ]);
                $this->logAndInfo('Layer/TaxonomyTheme relationship created');
            }

            $this->logAndInfo("Total layers created: $layerCreatedCount");
            $this->logAndInfo("Total taxonomy themes created: $themeCreatedCount");
            $this->logAndInfo("Total tracks updated: $trackUpdatedCount");
            $this->logAndInfo("Total tracks not found: $trackNotFoundCount");

            $this->logAndInfo('Processing completed successfully!');

            return 0;
        } catch (Exception $e) {
            $msg = 'Error during processing: '.$e->getMessage();
            $this->logAndInfo($msg, 'error');

            return 1;
        }
    }

    protected function slugify($string)
    {
        if (is_array($string) && isset($string['it'])) {
            $string = $string['it'];
        }

        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', html_entity_decode($string))));
    }

    /**
     * Logga il messaggio e lo mostra come info nel terminale
     *
     * @param  string  $message  Il messaggio da loggare e mostrare
     * @param  string  $level  Il livello di log (default: 'info')
     */
    protected function logAndInfo(string $message, string $level = 'info'): void
    {
        $this->$level($message);
        if ($level === 'warn') {
            $level = 'warning';
        }
        Log::$level($message);
    }
}
