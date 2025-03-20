<?php

namespace App\Console\Commands;

use App\Models\EcMedia;
use App\Models\EcTrack;
use App\Jobs\UpdateEcMedia;
use App\Models\TaxonomyTheme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symm\Gisconverter\Gisconverter;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\HeadingRowImport;

class SicaiCicloImp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 
        'geohub:sicai-ciclo-import 
            {xls : Path to the XLS file} 
            {uid : User id} 
            {theme-a-id : ID of the SICAI CICLO A theme taxonomy}
            {theme-b-id : ID of the SICAI CICLO B theme taxonomy}
            {--i : Interactive reading mode}
            {--check : Only check the parameters}
            {--skip-geometry : Do not import geometry}
            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from an XLS file';

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
        $xlsPath = $this->argument('xls');

        // CHECK IF FILE EXISTS

        if (!File::exists($xlsPath)) {
            $this->error("The file at path {$xlsPath} does not exist.");
            return 1;
        }

        $this->info("The file at path {$xlsPath} exists.");

        // Check if user exists
        $uid = $this->argument('uid');
        $user = \App\Models\User::find($uid);

        if (!$user) {
            $this->error("User with ID {$uid} does not exist.");
            return 1;
        }
        $this->info("User with ID {$uid} exists. Email: {$user->email}");

        // Check if taxonomyTheme theme-a-id and theme-b-id exist
        $themeAId = $this->argument('theme-a-id');
        $themeBId = $this->argument('theme-b-id');
        $themeA = TaxonomyTheme::find($themeAId);
        $themeB = TaxonomyTheme::find($themeBId);

        if (!$themeA) {
            $this->error("Theme with ID {$themeAId} does not exist.");
            return 1;
        }
        $this->info("Theme with ID {$themeAId} exists. Name: {$themeA->name}");

        if (!$themeB) {
            $this->error("Theme with ID {$themeBId} does not exist.");
            return 1;
        }
        $this->info("Theme with ID {$themeBId} exists. Name: {$themeB->name}");

        if ($this->option('check')) {
            $this->info('Check mode enabled. Exiting...');
            return 0;
        }

        // START CODE

        $rows = Excel::toArray(null, $xlsPath);

        foreach (array_slice($rows[0], 1) as $row) { // Skip the first row
            $mappedRow = [
                'tappa' => $row[0] ?? '',
                'verso' => $row[1] ?? '',
                'regione' => $row[2] ?? '',
                'km' => $row[3] ?? '',
                'partenza' => $row[4] ?? '',
                'quota_part' => $row[5] ?? '',
                'arrivo' => $row[6] ?? '',
                'quota_arri' => $row[7] ?? '',
                'd+' => $row[8] ?? '',
                'd-' => $row[9] ?? '',
                's+' => $row[10] ?? '',
                's-' => $row[11] ?? '',
                'descrizione' => $row[12] ?? '',
                'foto01' => $row[13] ?? '',
                'foto02' => $row[14] ?? '',
                'foto03' => $row[15] ?? '',
                'foto04' => $row[16] ?? '',
                'percorribilità' => $row[17] ?? '',
                'segnaletica_SICAI_MTB' => $row[18] ?? '',
                'note' => $row[19] ?? '',
                'referente' => $row[20] ?? '',
                'email' => $row[21] ?? '',
                'data' => $row[22] ?? '',
                'wmt' => $row[23] ?? '',
                'gpx' => $row[24] ?? '',
                'osmid' => $row[25] ?? '',
                'lunghezza' => $row[26] ?? '',
            ];

            $this->info($this->option('i') ? print_r($mappedRow, true) : 'Inserting row: ' . $mappedRow['tappa']);

            if ($this->option('i') && !$this->confirm('Do you wish to continue?', true)) {
                $this->info('Exiting...');
                return 1;
            }

            // Save the row to the database in model EcTrack
            $track = EcTrack::withoutEvents(function () use ($mappedRow, $uid) {
                return EcTrack::create([
                    'name' => $mappedRow['tappa'],
                    'from' => $mappedRow['partenza'],
                    'to' => $mappedRow['arrivo'],
                    'user_id' => $uid,
                ]);
            });

            $this->info('Track created with ID: ' . $track->id);

            // Update track with ref 
            $track->ref=$mappedRow['tappa'];

            // Update difficulty
            $track->difficulty = 'Salita: ' . $mappedRow['s+'] . ' / Discesa: ' . $mappedRow['s-'];

            // Update description
            $track->description = 
                  '<p><strong>Descrizione:</strong> ' . nl2br($mappedRow['descrizione']) . '</p>'
                . '<p><strong>Percorribilità:</strong> ' . (!empty($mappedRow['percorribilità']) ? $mappedRow['percorribilità'] : 'Sconosciuta') . '</p>'
                . '<p><strong>Ultimo aggiornamento:</strong> ' . date("d/m/Y", ($mappedRow['data'] - 25569) * 86400) . '</p>';

            // Udate taxonomy activity MTB ID=6
            $track->taxonomyActivities()->attach(6);
            
            // Update ascent only if is not empty
            if (!empty($mappedRow['d+'])) {
                $track->ascent = $mappedRow['d+'];
            }
            // Update descent only if is not empty
            if (!empty($mappedRow['d-'])) {
                $track->descent = $mappedRow['d-'];
            }

            // Update ele_from only if is not empty
            if (!empty($mappedRow['quota_part'])) {
                $track->ele_from = $mappedRow['quota_part'];
            }
            // Update ele_to only if is not empty
            if (!empty($mappedRow['quota_arri'])) {
                $track->ele_to = $mappedRow['quota_arri'];
            }

            // Update taxonomy theme A, switch on verso
            switch ($mappedRow['verso']) {
                case 'A':
                    $track->taxonomyThemes()->attach($themeAId);
                    break;
                case 'B':
                    $track->taxonomyThemes()->attach($themeBId);
                    break;
                default:
                    $this->error('Invalid value for "verso" column: ' . $mappedRow['verso']);
                    return 1;
            }

            $track->saveQuietly();
            $this->info('Updated track with ID: ' . $track->id);

            // Update geometry
            if (!$this->option('skip-geometry')) {
                $gpxUrl = "https://mtb.waymarkedtrails.org/api/v1/details/relation/{$mappedRow['osmid']}/geometry/gpx";
                $wmtUrl = "https://mtb.waymarkedtrails.org/#route?id={$mappedRow['osmid']}";
                
                // 1. Get the geometry from the GPX url
                $gpx = @file_get_contents($gpxUrl);
                if ($gpx === false) {
                    $this->error('Failed to fetch GPX data from URL: ' . $gpxUrl);
                    File::append(storage_path('logs/sicai_import_simple.log'), "Error fetching GPX data for track: {$track->name}, OSMID: {$mappedRow['osmid']}, WMT: {$wmtUrl}\n");
                } else {
                    // 2. Convert the GPX into a GEOJSON linestring
                    $geometry = Gisconverter::gpxToGeoJSON($gpx);
                    // 3. Save the GEOJSON linestring into the geometry field
                    $track->geometry = DB::select("SELECT ST_AsText(ST_Force3D(ST_LineMerge(ST_GeomFromGeoJSON('".$geometry."')))) As wkt")[0]->wkt;
        
                    try {
                        $track->saveQuietly();
                        $this->info('Geometry Updated: ' . $track->id);
                    } catch (\Exception $e) {
                        File::append(storage_path('logs/sicai_import_complete.log'), "Error track geometry: {$track->name}, OSMID: {$mappedRow['osmid']}, WMT: {$wmtUrl}, Error: " . $e->getMessage() . "\n");
                        File::append(storage_path('logs/sicai_import_simple.log'), "Error track geometry: {$track->name}, OSMID: {$mappedRow['osmid']}, WMT: {$wmtUrl}\n");
                        $this->error('Error saving track');
                    }    
                }
    
            }

            // IMAGE import
            $images = [];
            for ($i = 1; $i <= 4; $i++) {
                $fotoKey = 'foto0' . $i;
                if (!empty($mappedRow[$fotoKey])) {
                    $images[] = $mappedRow[$fotoKey];
                }
            }
            // Loop through images and save them only if count>0
            if (count($images) > 0) {
                $count = 0;
                foreach ($images as $imageUrl) {
                    $imageName = preg_replace('/[^A-Za-z0-9]/', '', $track->name) . $count . '.jpg';
                    $this->info('Image found: ' . $imageName. ' URL: ' . $imageUrl);

                    $localPath = storage_path($imageName) ;
                    try {
                        Storage::disk('public')->put($localPath, file_get_contents($imageUrl));
                        $ecMedia = EcMedia::create([
                            'name' => $imageName,
                            'url' => $localPath,
                        ]);
                        $ecMedia->user_id = $uid;
                        $ecMedia->saveQuietly();
                        UpdateEcMedia::dispatch($ecMedia);

                        // Attach first image to track as feature
                        if ($count == 0) {
                            $track->featureImage()->associate($ecMedia);
                            $track->saveQuietly();
                        }
                        // Attach other images to track gallery
                        if ($count > 0) {
                            $track->ecMedia()->attach($ecMedia->id);
                            $track->saveQuietly();
                        }
                        $count++;
                    } catch (\Exception $e) {
                        $this->error('Error fetching or saving image: ' . $imageUrl . ' - ' . $e->getMessage());
                        File::append(storage_path('logs/sicai_import_simple.log'), "Error fetching or saving image for track: {$track->name}, Image URL: {$imageUrl}" . "\n");
                        File::append(storage_path('logs/sicai_import_complete.log'), "Error fetching or saving image for track: {$track->name}, Image URL: {$imageUrl}, Error: " . $e->getMessage() . "\n");
                    }
                }
            }
            else {
                $this->info('No images found for track: ' . $track->id);
            }
    
        }

        return 0;
    }
}
