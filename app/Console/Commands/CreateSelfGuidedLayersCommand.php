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

class CreateSelfGuidedLayersCommand extends Command
{
    protected $author_id;

    protected $signature = 'geohub:create_self_guided_layers 
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
        $allTracks = EcTrack::query()->where('user_id', $authorId)->get();
        $appId = $this->argument('app_id');
        $generateEdges = $this->option('generate_edges');

        $this->info("Starting command with endpoint: $routeUrlApi and author ID: $authorId");
        Log::info("Starting command with endpoint: $routeUrlApi and author ID: $authorId");

        try {
            $this->info('Calling API...');
            Log::info('Calling API...');

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

            $this->info('API successfully loaded. Starting processing...');
            Log::info('API successfully loaded. Starting processing...');

            foreach ($layers as $index => $layer) {
                $this->info("Processing layer $index");
                Log::info("Processing layer $index");

                $layerNameRaw = $layer['title']['rendered'] ?? null;
                $layerName = html_entity_decode($layerNameRaw);
                $relatedTracks = $layer['n7webmap_route_related_track'] ?? [];

                if (! $layerName || count($relatedTracks) === 0) {
                    $msg = "Layer $index skipped (missing name or no associated tracks)";
                    $this->warn($msg);
                    Log::warning($msg);

                    continue;
                }

                $this->info("Layer name: $layerName");
                Log::info("Layer name: $layerName");

                $this->info('Associated tracks: '.count($relatedTracks));
                Log::info('Associated tracks: '.count($relatedTracks));

                $identifier = $this->slugify($layerName);
                $taxonomyTheme = TaxonomyTheme::where('identifier', $identifier)->first();

                if ($taxonomyTheme) {
                    $this->info("Existing TaxonomyTheme found with ID: {$taxonomyTheme->id}");
                    Log::info("Existing TaxonomyTheme found with ID: {$taxonomyTheme->id}");
                } else {
                    $taxonomyTheme = new TaxonomyTheme;
                    $taxonomyTheme->name = $layerName;
                    $taxonomyTheme->identifier = $identifier;
                    $taxonomyTheme->user_id = 1;
                    $taxonomyTheme->saveQuietly();
                    $themeCreatedCount++;
                    $this->info("TaxonomyTheme created with ID: {$taxonomyTheme->id}");
                    Log::info("TaxonomyTheme created with ID: {$taxonomyTheme->id}");
                }

                foreach ($relatedTracks as $track) {
                    $trackNameRaw = $track['post_title'] ?? null;
                    $trackName = html_entity_decode($trackNameRaw);

                    $geohubTrack = $allTracks->first(function ($t) use ($trackName) {
                        return html_entity_decode($t->name) === $trackName;
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
                        $this->info("  ↪ Track updated: {$geohubTrack->name} (ID: {$geohubTrack->id})");
                        Log::info("  ↪ Track updated: {$geohubTrack->name} (ID: {$geohubTrack->id})");
                    } else {
                        $trackNotFoundCount++;
                        $msg = "Track not found: $trackName";
                        $this->warn($msg);
                        Log::warning($msg);
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
                    $this->info("Media created with ID: {$ecMedia->id}, file: {$ecMedia->name}");
                    Log::info("Media created with ID: {$ecMedia->id}, file: {$ecMedia->name}");
                }

                $geohubLayer->save();
                $layerCreatedCount++;
                $this->info("Layer created with ID: {$geohubLayer->id}");
                Log::info("Layer created with ID: {$geohubLayer->id}");

                DB::table('taxonomy_themeables')->insert([
                    'taxonomy_theme_id' => $taxonomyTheme->id,
                    'taxonomy_themeable_id' => $geohubLayer->id,
                    'taxonomy_themeable_type' => Layer::class,
                ]);
                $this->info('Layer/TaxonomyTheme relationship created');
                Log::info('Layer/TaxonomyTheme relationship created');
            }

            $this->info("Total layers created: $layerCreatedCount");
            $this->info("Total taxonomy themes created: $themeCreatedCount");
            $this->info("Total tracks updated: $trackUpdatedCount");
            $this->info("Total tracks not found: $trackNotFoundCount");

            Log::info("Total layers created: $layerCreatedCount");
            Log::info("Total taxonomy themes created: $themeCreatedCount");
            Log::info("Total tracks updated: $trackUpdatedCount");
            Log::info("Total tracks not found: $trackNotFoundCount");

            $this->info('Processing completed successfully!');
            Log::info('Processing completed successfully!');

            return 0;
        } catch (Exception $e) {
            $msg = 'Error during processing: '.$e->getMessage();
            $this->error($msg);
            Log::error($msg);

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
}
