<?php

namespace App\Classes\OutSourceImporter;

use App\Models\OutSourceFeature;
use App\Providers\CurlServiceProvider;
use App\Traits\ImporterAndSyncTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symm\Gisconverter\Gisconverter;

class OutSourceImporterFeatureSentieriSardegna extends OutSourceImporterFeatureAbstract
{
    use ImporterAndSyncTrait;
    // DATA array
    protected array $params;
    protected array $tags;
    protected array $tax_difficulty;
    protected string $mediaGeom;
    protected string $poi_type;

    public function __construct(string $type, string $endpoint, string $source_id, bool $only_related_url = false, $tax_difficulty = [])
    {
        parent::__construct($type, $endpoint, $source_id, $only_related_url = false);

        // Initialize the new parameter
        $this->tax_difficulty = $tax_difficulty;
    }

    /**
     * It imports each track of the given list to the out_source_features table.
     *
     *
     * @return int The ID of OutSourceFeature created
     */
    public function importTrack()
    {
        $error_not_created = [];
        try {
            // Curl request to get the feature information from external source
            $url = 'https://www.sardegnasentieri.it/ss/track/' . $this->source_id . '?_format=json';
            $response = Http::get($url);
            $track = $response->json();

            // prepare feature parameters to pass to updateOrCreate function
            Log::info('Preparing OSF Track with external ID: ' . $this->source_id);
            $geometry = '';

            if (key_exists('gpx', $track['properties']) && !empty($track['properties']['gpx'])) {
                $gpx_content = Http::get($track['properties']['gpx'][0]);
                $geometry = Gisconverter::gpxToGeojson($gpx_content);
            } elseif (key_exists('geometry', $track)) {
                $geometry = json_encode($track['geometry']);
            } else {
                throw new Exception('No Geometry found');
            }

            $this->params['geometry'] = DB::select("SELECT ST_AsText(ST_LineMerge(ST_GeomFromGeoJSON('" . $geometry . "'))) As wkt")[0]->wkt;
            $this->mediaGeom = DB::select("SELECT ST_AsText(ST_StartPoint(ST_LineMerge(ST_GeomFromGeoJSON('" . $geometry . "')))) As wkt")[0]->wkt;
            $this->params['provider'] = get_class($this);
            $this->params['type'] = $this->type;
            $this->params['endpoint_slug'] = 'sardegna-sentieri-track';
            // $this->params['raw_data'] = json_encode($track);

            // prepare the value of tags data
            Log::info('Preparing OSF Track TAGS with external ID: ' . $this->source_id);
            $this->prepareTrackTagsJson($track);
            $this->params['tags'] = $this->tags;
            Log::info('Finished preparing OSF Track with external ID: ' . $this->source_id);
            Log::info('Starting creating OSF Track with external ID: ' . $this->source_id);
            return $this->create_or_update_feature($this->params);
        } catch (Exception $e) {
            array_push($error_not_created, $url);
            Log::info('Error creating OSF from external with id: ' . $this->source_id . "\n ERROR: " . $e->getMessage());
        }
        if ($error_not_created) {
            Log::info('Ec features not created from Source with URL: ');
            foreach ($error_not_created as $url) {
                Log::info($url);
            }
        }
    }

    /**
     * It imports each POI of the given list to the out_source_features table.
     *
     *
     * @return int The ID of OutSourceFeature created
     */
    public function importPoi()
    {

        $url = 'https://www.sardegnasentieri.it/ss/poi/' . $this->source_id . '?_format=json';
        $response = Http::get($url);
        $poi = $response->json();


        // prepare feature parameters to pass to updateOrCreate function
        Log::info('Preparing OSF POI with external ID: ' . $this->source_id);
        try {
            $geometry_poi = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('" . json_encode($poi['geometry']) . "')) As wkt")[0]->wkt;
            $this->params['geometry'] = $geometry_poi;
            $this->mediaGeom = $geometry_poi;
            $this->params['provider'] = get_class($this);
            $this->params['type'] = $this->type;
            $this->params['endpoint_slug'] = 'sardegna-sentieri-poi';
            $this->params['raw_data'] = json_encode($poi);

            // prepare the value of tags data
            Log::info('Preparing OSF POI TAGS with external ID: ' . $this->source_id);
            $this->tags = [];
            $this->preparePOITagsJson($poi);
            $this->params['tags'] = $this->tags;
            Log::info('Finished preparing OSF POI with external ID: ' . $this->source_id);
            Log::info('Starting creating OSF POI with external ID: ' . $this->source_id);
            return $this->create_or_update_feature($this->params);
        } catch (Exception $e) {
            Log::info('Error creating OSF : ' . $e);
        }
    }

    public function importMedia()
    {
        return 'getMediaList result';
    }

    /**
     * It updateOrCreate method of the class OutSourceFeature
     *
     * @param array $params The OutSourceFeature parameters to be added or updated
     * @return int The ID of OutSourceFeature created
     */
    protected function create_or_update_feature(array $params)
    {

        $feature = OutSourceFeature::updateOrCreate(
            [
                'source_id' => $this->source_id,
                'endpoint' => $this->endpoint
            ],
            $params
        );
        return $feature->id;
    }

    /**
     * It populates the tags variable with the track curl information so that it can be syncronized with EcTrack
     *
     * @param array $track The OutSourceFeature parameters to be added or updated
     *
     */
    protected function prepareTrackTagsJson($track)
    {
        Log::info('Preparing OSF Track TRANSLATIONS with external ID: ' . $this->source_id);
        if (isset($track['properties']['name'])) {
            $this->tags['name'] = $track['properties']['name'];
        }

        // Preparing the description for stato di validazione
        if (isset($track['properties']['description'])) {
            $this->tags['description'] = [];
        }

        if (isset($track['properties']['codice_cai'])) {
            $this->tags['ref'] = $track['properties']['codice_cai'];
        }

        // Related Poi of vicinity
        if ($this->params['geometry']) {
            $geometry = $this->params['geometry'];
            $related_pois = DB::select("SELECT id from out_source_features WHERE type='poi' and endpoint='https://www.sardegnasentieri.it/ss/listpoi/?_format=json' and ST_Contains(ST_BUFFER(ST_SetSRID(ST_GeomFromText('$geometry'),4326),0.01, 'endcap=round join=round'),geometry::geometry);");

            if (is_array($related_pois) && !empty($related_pois)) {
                foreach ($related_pois as $poi) {
                    $this->tags['related_poi'][] = $poi->id;
                }
            }
        }

        // Add From e To to the Track
        if (isset($track['properties']['partenza'])) {
            $source_id = $track['properties']['partenza'];
            $from = collect(DB::select("SELECT tags FROM out_source_features where endpoint='https://www.sardegnasentieri.it/ss/listpoi/?_format=json' and source_id='$source_id'"))->pluck('tags')->toArray();
            if (is_array($from) && !empty($from)) {
                $from = json_decode($from[0], true);
            }
            $this->tags['from'] = $from['name']['it'];
        }
        if (isset($track['properties']['arrivo'])) {
            $source_id = $track['properties']['arrivo'];
            $to = collect(DB::select("SELECT tags FROM out_source_features where endpoint='https://www.sardegnasentieri.it/ss/listpoi/?_format=json' and source_id='$source_id'"))->pluck('tags')->toArray();
            if (is_array($to) && !empty($to)) {
                $to = json_decode($to[0], true);
            }
            $this->tags['to'] = $to['name']['it'];
        }

        // Adding originale public url to related_url
        if (isset($track['properties']['url'])) {
            $this->tags['related_url']['sardegnasentieri.it'] = $track['properties']['url'];
        }

        // Processing the theme
        if ($track['properties']['type'] == 'itinerario') {
            $this->tags['theme'][] = 'sardegnas-itinerario';
            $this->tags['color'] = '#608d0d';
        } else {
            $this->tags['theme'][] = 'sardegnas-sentiero';
        }

        $this->tags['description']['it'] = '';
        $this->tags['description']['en'] = '';

        // Processing the Theme stato di validazione
        if (isset($track['properties']['taxonomies'])) {
            Log::info('Preparing OSF TRACK theme MAPPING with external ID: ' . $this->source_id);

            $path = parse_url($this->endpoint);
            $file_name = str_replace('.', '-', $path['host']);
            if (Storage::disk('mapping')->exists($file_name . '.json')) {
                $taxonomy_map = Storage::disk('mapping')->get($file_name . '.json');
                $json_taxonomy_theme = json_decode($taxonomy_map, true)['theme'];

                // Adding the Stato di validazione to the description
                if (!empty($json_taxonomy_theme)) {
                    foreach ($track['properties']['taxonomies'] as $tax => $idList) {
                        if ($tax == 'stato_di_validazione') {
                            if (is_array($idList)) {
                                foreach ($idList as $id) {
                                    if (key_exists($id, $json_taxonomy_theme)) {
                                        $this->tags['description']['it'] .= isset($json_taxonomy_theme[$id]['source_title']['it']) ? '<h3>Stato di validazione:</h3><p><strong>' . $json_taxonomy_theme[$id]['source_title']['it'] . '</strong></p><a target="_blank" href="https://www.sardegnasentieri.it/pagina-base/note-legali">Maggiori info</a>' : '';
                                        $this->tags['description']['en'] .= isset($json_taxonomy_theme[$id]['source_title']['en']) ? '<h3>Validation status:</h3><p><strong>' . $json_taxonomy_theme[$id]['source_title']['en'] . '</strong></p><a target="_blank" href="https://www.sardegnasentieri.it/pagina-base/note-legali">More info</a>' : '';
                                    }
                                }
                            } else {
                                if (key_exists($idList, $json_taxonomy_theme)) {
                                    $this->tags['description']['it'] .= isset($json_taxonomy_theme[$idList]['source_title']['it']) ? '<h3>Stato di validazione:</h3><p><strong>' . $json_taxonomy_theme[$idList]['source_title']['it'] . '</strong></p><a target="_blank" href="https://www.sardegnasentieri.it/pagina-base/note-legali">Maggiori info</a>' : '';
                                    $this->tags['description']['en'] .= isset($json_taxonomy_theme[$idList]['source_title']['en']) ? '<h3>Validation status:</h3><p><strong>' . $json_taxonomy_theme[$idList]['source_title']['en'] . '</strong></p><a target="_blank" href="https://www.sardegnasentieri.it/pagina-base/note-legali">Maggiori info</a>' : '';
                                }
                            }
                        }
                    }
                }

                // Adding the difficulty to the description
                if (!empty($this->tax_difficulty)) {
                    $more_info_link = false;
                    foreach ($track['properties']['taxonomies'] as $tax => $idList) {
                        if ($tax == 'categorie_fruibilita_sentieri') {
                            if (is_array($idList) && !empty($idList)) {
                                $this->tags['description']['it'] .= '<h3>Difficoltà:</h3>';
                                foreach ($idList as $id) {
                                    if (key_exists($id, $this->tax_difficulty)) {
                                        $more_info_link = true;
                                        if ($this->tax_difficulty[$id]['parent'] != 'NULL') {
                                            $this->tags['description']['it'] .= '<p>' . $this->tax_difficulty[$this->tax_difficulty[$id]['parent']]['name']['it'] . ' > ' . $this->tax_difficulty[$id]['name']['it'] . '</p>';
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($more_info_link) {
                        $this->tags['description']['it'] .= '<a target="_blank" href="https://www.sardegnasentieri.it/gradi_difficolt%C3%A0">Maggiori info</a>';

                    }
                }
            }
        }

        // Adding the description after the Stato di validazione
        if (isset($track['properties']['description'])) {
            $this->tags['description']['it'] .= isset($track['properties']['description']['it']) ? '<h3>Descrizione:</h3>' : '';
            $this->tags['description']['it'] .= isset($track['properties']['description']['it']) ? $track['properties']['description']['it'] : '';
            $this->tags['description']['en'] .= isset($track['properties']['description']['en']) ? '<h3>Description:</h3>' : '';
            $this->tags['description']['en'] .= isset($track['properties']['description']['en']) ? $track['properties']['description']['en'] : '';
        }

        // Processing the Theme Tipologia Itinerari
        if (isset($track['properties']['taxonomies'])) {
            Log::info('Preparing OSF TRACK theme MAPPING with external ID: ' . $this->source_id);

            $path = parse_url($this->endpoint);
            $file_name = str_replace('.', '-', $path['host']);
            if (Storage::disk('mapping')->exists($file_name . '.json')) {
                $taxonomy_map = Storage::disk('mapping')->get($file_name . '.json');
                $json_taxonomy_theme = json_decode($taxonomy_map, true)['theme'];

                if (!empty($json_taxonomy_theme)) {
                    foreach ($track['properties']['taxonomies'] as $tax => $idList) {
                        if ($tax == 'tipologia_itinerari') {
                            if (is_array($idList)) {
                                foreach ($idList as $id) {
                                    if (key_exists($id, $json_taxonomy_theme)) {
                                        $this->tags['theme'][] = $json_taxonomy_theme[$id]['geohub_identifier'];
                                    }
                                }
                            } else {
                                if (key_exists($idList, $json_taxonomy_theme)) {
                                    $this->tags['theme'][] = $json_taxonomy_theme[$idList]['geohub_identifier'];
                                }
                            }
                        }
                    }
                }
            }
        }

        // Processing the Activity
        if (isset($track['properties']['taxonomies'])) {
            Log::info('Preparing OSF TRACK activity MAPPING with external ID: ' . $this->source_id);

            $path = parse_url($this->endpoint);
            $file_name = str_replace('.', '-', $path['host']);
            if (Storage::disk('mapping')->exists($file_name . '.json')) {
                $taxonomy_map = Storage::disk('mapping')->get($file_name . '.json');

                if (!empty(json_decode($taxonomy_map, true)['activity'])) {
                    foreach ($track['properties']['taxonomies'] as $tax => $idList) {
                        if ($tax == 'tipologia_sentieri') {
                            if (is_array($idList)) {
                                foreach ($idList as $id) {
                                    if (key_exists($id, json_decode($taxonomy_map, true)['activity'])) {
                                        $this->tags['activity'][] = json_decode($taxonomy_map, true)['activity'][$id]['geohub_identifier'];
                                    }
                                }
                            } else {
                                if (key_exists($idList, json_decode($taxonomy_map, true)['activity'])) {
                                    $this->tags['activity'][] = json_decode($taxonomy_map, true)['activity'][$idList]['geohub_identifier'];
                                }
                            }
                        }
                    }
                }
            }
        }

        // Processing the feature image of Track
        if (isset($track['properties']['immagine_principale'])) {
            Log::info('Preparing OSF Track FEATURE_IMAGE with external ID: ' . $this->source_id);
            if ($track['properties']['immagine_principale']) {
                $image_source_id = $this->source_id . 666;
                $this->tags['feature_image'] = $this->createOSFMediaFromLink($track['properties']['immagine_principale'], $image_source_id);
            } else {
                Log::info('ERROR reaching media: ' . $track['properties']['immagine_principale']);
            }
        }

        // Processing the image Gallery of Track
        if (isset($track['properties']['galleria_immagini'])) {
            if (is_array($track['properties']['galleria_immagini'])) {
                Log::info('Preparing OSF Track IMAGE_GALLERY with external ID: ' . $this->source_id);
                $count = 777;
                foreach($track['properties']['galleria_immagini'] as $img) {
                    if ($img) {
                        $image_source_id = $this->source_id . $count;
                        $this->tags['image_gallery'][] = $this->createOSFMediaFromLink($img, $image_source_id);
                        $count++;
                    } else {
                        Log::info('ERROR reaching media: ' . $img);
                    }
                }
            }
        }
    }

    /**
     * It populates the tags variable with the POI curl information so that it can be syncronized with EcPOI
     *
     * @param array $poi The OutSourceFeature parameters to be added or updated
     *
     */
    protected function preparePOITagsJson($poi)
    {
        Log::info('Preparing OSF POI TRANSLATIONS with external ID: ' . $this->source_id);
        if (isset($poi['properties']['name'])) {
            $this->tags['name'] = $poi['properties']['name'];
        }
        if (isset($poi['properties']['description'])) {
            $this->tags['description'] = $poi['properties']['description'];
        }

        if (isset($poi['properties']['codice'])) {
            $this->tags['code'] = $poi['properties']['codice'];
        }

        if (isset($poi['properties']['addr_locality'])) {
            $this->tags['addr_complete'] = $poi['properties']['addr_locality'];
        }

        if (!$this->only_related_url) {
            // Processing the poi_type
            if (isset($poi['properties']['taxonomies'])) {
                Log::info('Preparing OSF POI POI_TYPE MAPPING with external ID: ' . $this->source_id);

                $path = parse_url($this->endpoint);
                $file_name = str_replace('.', '-', $path['host']);
                if (Storage::disk('mapping')->exists($file_name . '.json')) {
                    $taxonomy_map = Storage::disk('mapping')->get($file_name . '.json');
                    $json_poi_type = json_decode($taxonomy_map, true)['poi_type'];

                    if (!empty($json_poi_type)) {
                        foreach ($poi['properties']['taxonomies'] as $tax => $idList) {
                            if (in_array($tax, ['servizi','tipologia_poi'])) {
                                foreach ($idList as $id) {
                                    if (key_exists($id, $json_poi_type)) {
                                        if (!$json_poi_type[$id]['skip'] && !empty($json_poi_type[$id]['geohub_identifier'])) {
                                            Log::info('tax added : ' . $id);
                                            $this->tags['poi_type'][] = $json_poi_type[$id]['geohub_identifier'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Processing the feature image of POI
            if (isset($poi['properties']['immagine_principale'])) {
                Log::info('Preparing OSF POI FEATURE_IMAGE with external ID: ' . $this->source_id);

                if ($poi['properties']['immagine_principale']) {
                    $image_source_id = $this->source_id . 555;
                    $this->tags['feature_image'] = $this->createOSFMediaFromLink($poi['properties']['immagine_principale'], $image_source_id);
                } else {
                    Log::info('ERROR reaching media: ' . $poi['properties']['immagine_principale']);
                }
            }
        }

        // Adding originale public url to related_url
        if (isset($poi['properties']['url'])) {
            $this->tags['related_url']['sardegnasentieri.it'] = $poi['properties']['url'];
        }
    }

    protected function createOSFMediaFromLink($image_url, $image_source_id)
    {
        $tags = [];
        try {
            // Saving the Media in to the s3-osfmedia storage (.env in production)
            $storage_name = config('geohub.osf_media_storage_name');
            Log::info('Geting image from url: ' . $image_url);
            $url_encoded = preg_replace_callback('/[^\x20-\x7f]/', function ($match) {
                return urlencode($match[0]);
            }, $image_url);
            $contents = Http::get($url_encoded);
            $basename_explode = explode('.', basename($image_url));
            $basename = basename($image_url);
            $s3_osfmedia = Storage::disk($storage_name);
            // $osf_name_tmp = sha1($basename[0]) . '.' . $basename[1];
            $fullPathName = 'sardegna-sentieri/' . $basename;
            $s3_osfmedia->put($fullPathName, $contents->body());

            Log::info('Saved OSF Media with name: ' . $basename);
            $tags['url'] = ($s3_osfmedia->exists($fullPathName)) ? $fullPathName : '';
            $tags['name']['it'] = $basename_explode[0];
        } catch(Exception $e) {
            echo $e;
            Log::info('Saving media in s3-osfmedia error:' . $e);
        }

        Log::info('Preparing OSF MEDIA TAGS with external ID: ' . $image_source_id);
        $params['tags'] = $tags;
        $params['type'] = 'media';
        $params['provider'] = get_class($this);
        $params['geometry'] = $this->mediaGeom;
        Log::info('Starting creating OSF MEDIA with external ID: ' . $image_source_id);
        $feature = OutSourceFeature::updateOrCreate(
            [
                'source_id' => $image_source_id,
                'endpoint' => $this->endpoint
            ],
            $params
        );
        return $feature->id;
    }
}
