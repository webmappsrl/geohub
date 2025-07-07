<?php

namespace App\Console\Commands;

use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OutcropediaSyncUgcPoiFromOutSourceFeatureCommand extends Command
{
    protected $signature = 'geohub:outcropedia_sync_ugc_poi_from_outsource';

    protected $description = 'Sync UGC POIs and Users from out_source_features table specific to Outcropedia';

    public function handle(): int
    {
        // === BASIC CONFIGURATION ===
        $appId = 82;
        $sku = 'it.webmapp.outcropedia';
        $userMappingPath = storage_path('importer/mapping/outcropedia-users.json');
        $taxonomyMappingPath = storage_path('importer/mapping/outcropedia-tectask-org.json');

        // === VALIDATE MAPPING FILES ===
        if (! file_exists($userMappingPath)) {
            $this->error("Mapping file not found at $userMappingPath");
            Log::error("Mapping file not found at $userMappingPath");

            return 1;
        }

        if (! file_exists($taxonomyMappingPath)) {
            $this->error("Taxonomy mapping file not found at $taxonomyMappingPath");
            Log::error("Taxonomy mapping file not found at $taxonomyMappingPath");

            return 1;
        }

        $userMapping = json_decode(file_get_contents($userMappingPath), true);
        $taxonomyMapping = json_decode(file_get_contents($taxonomyMappingPath), true);

        if (! is_array($userMapping) || ! is_array($taxonomyMapping)) {
            $this->error('Invalid JSON format in one of the mapping files.');
            Log::error('Invalid JSON format in one of the mapping files.');

            return 1;
        }

        // === SELECT FEATURES FROM OUT_SOURCE_FEATURES ===
        $features = DB::table('out_source_features')
            ->where('type', 'poi')
            ->where('provider', 'App\\Classes\\OutSourceImporter\\OutSourceImporterFeatureWP')
            ->where('endpoint', 'https://outcropedia.tectask.org')
            ->get();

        foreach ($features as $feature) {
            $raw = json_decode($feature->raw_data, true);

            // === GET ASSOCIATED USER ===
            if (! isset($raw['author'])) {
                $this->warn("Missing author for feature ID: $feature->id");
                Log::warning("Missing author for feature ID: $feature->id");

                continue;
            }

            $authorId = $raw['author'];
            $userData = collect($userMapping)->firstWhere('ID', $authorId);
            if (! $userData) {
                $this->warn("User mapping not found for author ID: $authorId");
                Log::warning("User mapping not found for author ID: $authorId");

                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $userData['user_email']],
                [
                    'name' => $userData['display_name'],
                    'password' => Hash::make(env('OUTCROPEDIA_USER_DEFAULT_PASSWORD')),
                    'sku' => $sku,
                    'app_id' => $appId,
                ]
            );

            if (! $user->hasRole('Contributor')) {
                $user->assignRole('Contributor');
            }

            // === BUILD GEOMETRY ===
            if (isset($raw['n7webmap_coord']['lat']) && isset($raw['n7webmap_coord']['lng'])) {
                $lat = $raw['n7webmap_coord']['lat'];
                $lng = $raw['n7webmap_coord']['lng'];
                $geometry = DB::raw("ST_SetSRID(ST_MakePoint($lng, $lat), 4326)");
            } else {
                $this->warn("Missing coordinates for POI (feature ID: $feature->id), POI not created.");
                Log::warning("Missing coordinates for POI (feature ID: $feature->id), POI not created.");

                continue;
            }

            // === DETERMINE WAYPOINT TYPE ===
            $waypointType = 'poi';
            if (! empty($raw['webmapp_category'][0])) {
                $catId = (string) $raw['webmapp_category'][0];
                if (isset($taxonomyMapping['poi_type'][$catId]['geohub_identifier'])) {
                    $waypointType = $taxonomyMapping['poi_type'][$catId]['geohub_identifier'];
                } else {
                    $this->warn("Taxonomy mapping not found for POI (feature ID: $feature->id)");
                    Log::warning("Taxonomy mapping not found for POI (feature ID: $feature->id)");
                }
            }
            $createdAt = $raw['date'] ?? now()->toIso8601String();
            $updatedAt = $raw['modified'] ?? now()->toIso8601String();

            // === GENERATE POI NAME ===
            $titleRendered = isset($raw['title']['rendered']) ? html_entity_decode($raw['title']['rendered'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
            $poiName = $titleRendered ?? $this->generateDefaultPoiName($waypointType, $createdAt, $feature->id);

            // === CREATE UGC POI ===
            $poi = new UgcPoi;
            $poi->user_id = $user->id;
            $poi->app_id = $appId;
            $poi->sku = $sku;
            $poi->name = $poiName;
            $poi->description = strip_tags($raw['content']['rendered'] ?? '');
            $poi->raw_data = $feature->raw_data;
            $poi->geometry = $geometry;

            $poi->properties = [
                'form' => [
                    'id' => 'poi',
                    'index' => 0,
                    'title' => $poi->name,
                    'description' => $poi->description,
                    'waypointtype' => $waypointType,
                ],
                'name' => $poi->name,
                'type' => 'waypoint',
                'media' => [],
                'app_id' => (string) $appId,
                'createdAt' => $createdAt,
                'updatedAt' => $updatedAt,
            ];
            $poi->save();

            // === HANDLE MEDIA ===
            if (! empty($raw['yoast_head_json']['og_image'][0]['url'])) {
                $imageUrl = $raw['yoast_head_json']['og_image'][0]['url'];
                try {
                    $imageContents = file_get_contents($imageUrl);
                    $filename = 'image_'.uniqid().'.jpg';
                    $relativePath = 'media/images/ugc/'.$filename;
                    Storage::disk('public')->put($relativePath, $imageContents);

                    $media = new UgcMedia;
                    $media->user_id = $user->id;
                    $media->app_id = $appId;
                    $media->sku = $sku;
                    $media->relative_url = $relativePath;
                    $media->name = $poi->name;
                    $media->description = $poi->description;
                    $media->raw_data = json_encode($raw['yoast_head_json']['og_image'][0]);
                    $media->geometry = $geometry;
                    $media->save();

                    $poi->ugc_media()->attach($media->id);
                } catch (\Exception $e) {
                    $this->warn("Failed saving media for POI {$poi->id}: {$e->getMessage()}");
                    Log::warning("Failed saving media for POI {$poi->id}: {$e->getMessage()}");
                }
            }

            // === LOG SUCCESS ===
            $this->info("POI created: ID {$poi->id} | User: {$user->email}");
            Log::info("POI created: ID {$poi->id} | User: {$user->email}");
        }

        return 0;
    }

    /**
     * Generate default POI name using waypoint type and creation date
     */
    private function generateDefaultPoiName(string $waypointType, ?string $createdAt, int $featureId): string
    {
        // Handle null or empty date - return only waypoint type
        if (empty($createdAt)) {
            $this->warn("Empty or null date for POI name (feature ID: $featureId), using only waypoint type");
            Log::warning("Empty or null date for POI name (feature ID: $featureId), using only waypoint type");

            return $waypointType;
        }

        try {
            $dateTime = new \DateTime($createdAt);
            $year = $dateTime->format('Y');
            $month = $dateTime->format('m');
            $day = $dateTime->format('d');
            $hours = $dateTime->format('H');
            $minutes = $dateTime->format('i');

            return "$waypointType $year/$month/$day $hours:$minutes";
        } catch (\Exception $e) {
            $this->warn("Error parsing date '$createdAt' for POI name (feature ID: $featureId), using only waypoint type");
            Log::warning("Error parsing date '$createdAt' for POI name (feature ID: $featureId), using only waypoint type");

            return $waypointType;
        }
    }
}
