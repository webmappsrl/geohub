<?php

namespace App\Classes\OutSourceImporter;

use App\Http\Facades\OsmClient;
use App\Models\OutSourceFeature;
use App\Providers\CurlServiceProvider;
use App\Traits\ImporterAndSyncTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Providers\OsmServiceProvider;
use Symm\Gisconverter\Gisconverter;

class OutSourceImporterFeatureWP extends OutSourceImporterFeatureAbstract { 
    use ImporterAndSyncTrait;
    // DATA array
    protected array $params;
    protected array $tags;
    protected string $mediaGeom;

    /**
     * It imports each track of the given list to the out_source_features table.
     * 
     *
     * @return int The ID of OutSourceFeature created 
     */
    public function importTrack(){
        $error_not_created = [];
        try {
            // Curl request to get the feature information from external source
            $url = $this->endpoint.'/wp-json/wp/v2/track/'.$this->source_id;
            $track = $this->curlRequest($url);
    
            // prepare feature parameters to pass to updateOrCreate function
            Log::info('Preparing OSF Track with external ID: '.$this->source_id);
            if (isset($track['osmid']) && !empty($track['osmid'])) {
                $osmid = $track['osmid'];
                $this->tags['osmid'] = $track['osmid'];

                $osmClient = new OsmClient;
                $geojson_content = $osmClient::getGeojson('relation/'.$osmid);
                $geojson_content = json_decode($geojson_content);
                $geojson_content = json_encode($geojson_content->geometry);
                $this->params['geometry'] = DB::select("SELECT ST_AsText(ST_LineMerge(ST_GeomFromGeoJSON('".$geojson_content."'))) As wkt")[0]->wkt;
                $this->mediaGeom = DB::select("SELECT ST_AsText(ST_StartPoint(ST_LineMerge(ST_GeomFromGeoJSON('".$geojson_content."')))) As wkt")[0]->wkt;
            } else {
                $this->params['geometry'] = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('".json_encode(unserialize($track['n7webmap_geojson']))."')) As wkt")[0]->wkt;
                $this->mediaGeom = DB::select("SELECT ST_AsText(ST_StartPoint(ST_GeomFromGeoJSON('".json_encode(unserialize($track['n7webmap_geojson']))."'))) As wkt")[0]->wkt;
            }
            $this->params['provider'] = get_class($this);
            $this->params['type'] = $this->type;
            $this->params['raw_data'] = json_encode($track);
    
            // prepare the value of tags data
            Log::info('Preparing OSF Track TAGS with external ID: '.$this->source_id);
            $this->prepareTrackTagsJson($track);
            $this->params['tags'] = $this->tags;
            Log::info('Finished preparing OSF Track with external ID: '.$this->source_id);
            Log::info('Starting creating OSF Track with external ID: '.$this->source_id);
            return $this->create_or_update_feature($this->params);
        } catch (Exception $e) {
            array_push($error_not_created,$url);
            Log::info('Error creating EcPoi from OSF with id: '.$this->source_id."\n ERROR: ".$e->getMessage());
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
    public function importPoi(){
        // Curl request to get the feature information from external source
        $url = $this->endpoint.'/wp-json/wp/v2/poi/'.$this->source_id;
        $poi = $this->curlRequest($url);
        
        
        // prepare feature parameters to pass to updateOrCreate function
        Log::info('Preparing OSF POI with external ID: '.$this->source_id);
        try{
            if (!is_numeric($poi['n7webmap_coord']['lng'])  || !is_numeric($poi['n7webmap_coord']['lat'])) 
                throw new Exception('POI missing coordinates');

            $geometry = '{"type":"Point","coordinates":['.$poi['n7webmap_coord']['lng'].','.$poi['n7webmap_coord']['lat'].']}';
            $geometry_poi = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('$geometry')) As wkt")[0]->wkt;
            $this->params['geometry'] = $geometry_poi;
            $this->mediaGeom = $geometry_poi;
            $this->params['provider'] = get_class($this);
            $this->params['type'] = $this->type;
            $this->params['raw_data'] = json_encode($poi);
            
            // prepare the value of tags data
            Log::info('Preparing OSF POI TAGS with external ID: '.$this->source_id);
            $this->tags = [];
            $this->preparePOITagsJson($poi);
            $this->params['tags'] = $this->tags;
            Log::info('Finished preparing OSF POI with external ID: '.$this->source_id);
            Log::info('Starting creating OSF POI with external ID: '.$this->source_id);
            return $this->create_or_update_feature($this->params);
        } catch (Exception $e) {
            Log::info('Error creating OSF : '.$e);
        }
    }

    public function importMedia(){
        return 'getMediaList result';
    }

    /**
     * It updateOrCreate method of the class OutSourceFeature
     * 
     * @param array $params The OutSourceFeature parameters to be added or updated 
     * @return int The ID of OutSourceFeature created 
     */
    protected function create_or_update_feature(array $params) {

        $feature = OutSourceFeature::updateOrCreate(
            [
                'source_id' => $this->source_id,
                'endpoint' => $this->endpoint
            ],
            $params);
        return $feature->id;
    }

    /**
     * It populates the tags variable with the track curl information so that it can be syncronized with EcTrack 
     * 
     * @param array $track The OutSourceFeature parameters to be added or updated 
     * 
     */
    protected function prepareTrackTagsJson($track){
        $domain_path = parse_url($this->endpoint);
        Log::info('Preparing OSF Track TRANSLATIONS with external ID: '.$this->source_id);
        $this->tags['name'][explode('_',$track['wpml_current_locale'])[0]] = html_entity_decode($track['title']['rendered']);
        $this->tags['description'][explode('_',$track['wpml_current_locale'])[0]] = html_entity_decode($track['content']['rendered']);
        $this->tags['excerpt'][explode('_',$track['wpml_current_locale'])[0]] = html_entity_decode($track['excerpt']['rendered']);
        // Add audio for default language
        // $this->tags['audio'][explode('_',$track['wpml_current_locale'])[0]] = $this->uploadAudioAWS('https://a.webmapp.it/'.$domain_path['host'].'/media/audios/'.$track['id'].'_'.explode('_',$track['wpml_current_locale'])[0].'.mp3',explode('_',$track['wpml_current_locale'])[0]);
        if(!empty($track['wpml_translations'])) {
            foreach($track['wpml_translations'] as $lang){
                $locale = explode('_',$lang['locale']);
                $this->tags['name'][$locale[0]] = html_entity_decode($lang['post_title']);
                // Curl request to get the feature translation from external source
                $url = $this->endpoint.'/wp-json/wp/v2/track/'.$lang['id'];
                $track_decode = $this->curlRequest($url);
                $this->tags['description'][$locale[0]] = html_entity_decode($track_decode['content']['rendered']);
                $this->tags['excerpt'][$locale[0]] = html_entity_decode($track_decode['excerpt']['rendered']); 
                // Add audio for other languages
                // $this->tags['audio'][$locale[0]] = $this->uploadAudioAWS('https://a.webmapp.it/'.$domain_path['host'].'/media/audios/'.$track['id'].'_'.$locale[0].'.mp3',$locale[0]);
            }
        }
        $this->tags['from'] = html_entity_decode($track['n7webmap_start']);
        $this->tags['to'] = html_entity_decode($track['n7webmap_end']);
        $this->tags['ele_from'] = $track['ele:from'];
        $this->tags['ele_to'] = $track['ele:to'];
        $this->tags['ele_max'] = $track['ele:max'];
        $this->tags['ele_min'] = $track['ele:min'];
        $this->tags['distance'] = $track['distance'];
        $this->tags['difficulty'] = $track['cai_scale'];

        // Adds the EcOutSource:poi ID to EcOutSource:track's related_poi tags 
        if (isset($track['n7webmap_related_poi']) && is_array($track['n7webmap_related_poi'])) {
            Log::info('Preparing OSF Track RELATED_POI with external ID: '.$this->source_id);
            $this->tags['related_poi'] = array();
            foreach($track['n7webmap_related_poi'] as $poi) {
                $OSF_poi = OutSourceFeature::where('endpoint',$this->endpoint)
                            ->where('source_id',$poi['ID'])
                            ->first();
                if ($OSF_poi && !is_null($OSF_poi)) {
                    array_push($this->tags['related_poi'],$OSF_poi->id);
                }
            }
        }

        // Adds related url to the track
        if (isset($track['n7webmap_rpt_related_url'])) {
            if (is_array($track['n7webmap_rpt_related_url'])) {
                foreach($track['n7webmap_rpt_related_url'] as $url) {
                    if (is_array($url)) {
                        $related_url_name = parse_url($url['net7webmap_related_url']);
                        $link = $url['net7webmap_related_url'];
                    } else {
                        $related_url_name = parse_url($url);
                        $link = $url;
                    }
                    $host = $related_url_name;
                    if (isset($related_url_name['host']) && $related_url_name['host']) {
                        $host = $related_url_name['host'];
                    }
                    if (!empty($link) && !empty($host)) {
                        $this->tags['related_url'][$host] = $link;
                    }
                }
            } else {
                $this->tags['related_url'] = $track['n7webmap_rpt_related_url'];
            }
        }

        // Processing the feature image of Track
        if (isset($track['featured_media']) && $track['featured_media']) {
            Log::info('Preparing OSF Track FEATURE_IMAGE with external ID: '.$this->source_id);
            $url = $this->endpoint.'/wp-json/wp/v2/media/'.$track['featured_media'];
            $media = $this->curlRequest($url);
            if ($media) {
                $this->tags['feature_image'] = $this->createOSFMediaFromWP($media);
            } else {
                Log::info('ERROR reaching media: '.$url);
            }
        }

        // Processing the image Gallery of Track
        if (isset($track['n7webmap_track_media_gallery']) && $track['n7webmap_track_media_gallery']) {
            if (is_array($track['n7webmap_track_media_gallery'])){
                Log::info('Preparing OSF Track IMAGE_GALLERY with external ID: '.$this->source_id);
                foreach($track['n7webmap_track_media_gallery'] as $img) {
                    $url = $this->endpoint.'/wp-json/wp/v2/media/'.$img['id'];
                    $media = $this->curlRequest($url);
                    if ($media) {
                        $this->tags['image_gallery'][] = $this->createOSFMediaFromWP($media);
                    } else {
                        Log::info('ERROR reaching media: '.$url);
                    }
                }
            }
        }

        // Processing the activity
        $path = parse_url($this->endpoint);
        $file_name = str_replace('.','-',$path['host']);
        Log::info('Preparing OSF Track ACTIVITY MAPPING with external ID: '.$this->source_id);
        if (Storage::disk('mapping')->exists($file_name.'.json')) {
            $taxonomy_map = Storage::disk('mapping')->get($file_name.'.json');

            if (!empty(json_decode($taxonomy_map,true)['activity']) && $track['activity']) {
                foreach ($track['activity'] as $tax) {
                    $this->tags['activity'][] = json_decode($taxonomy_map,true)['activity'][$tax]['geohub_identifier'];
                }
            }
        }

        // Processing the theme
        $path = parse_url($this->endpoint);
        $file_name = str_replace('.','-',$path['host']);
        Log::info('Preparing OSF Track THEME MAPPING with external ID: '.$this->source_id);
        if (Storage::disk('mapping')->exists($file_name.'.json')) {
            $taxonomy_map = Storage::disk('mapping')->get($file_name.'.json');

            if (!empty(json_decode($taxonomy_map,true)['theme']) && $track['theme']) {
                foreach ($track['theme'] as $tax) {
                    $this->tags['theme'][] = json_decode($taxonomy_map,true)['theme'][$tax]['geohub_identifier'];
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
    protected function preparePOITagsJson($poi){
        if (!$this->only_related_url) { // skip import if only related url is true
        Log::info('Preparing OSF POI TRANSLATIONS with external ID: '.$this->source_id);
        $this->tags['name'][explode('_',$poi['wpml_current_locale'])[0]] = html_entity_decode($poi['title']['rendered']);
        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] = html_entity_decode($poi['content']['rendered']);

        // Adding the name from Sardinian to Italian for Campos project
        if ($this->endpoint == 'https://cordinamentu-campos.org') {
            $this->tags['name']['it'] = html_entity_decode($poi['title']['rendered']);
            $this->tags['description']['it'] = html_entity_decode($poi['content']['rendered']);
        } 

        // Adding ACF of Itinera Romanica to description
        if (isset($poi['acf'])){
            if (isset($poi['acf']['titolo_alternativo']) && $poi['acf']['titolo_alternativo']) {
                $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= "</br><p><strong>Titolo alternativo:</strong></p>";
                $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= html_entity_decode($poi['acf']['titolo_alternativo']);
            }
            if (isset($poi['acf']['rilevanza_storica']) && $poi['acf']['rilevanza_storica']) {
                $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= "</br><p><strong>Rilevanza storica:</strong></p>";
                $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= html_entity_decode($poi['acf']['rilevanza_storica']);
            }
            if (isset($poi['acf']['rilevanza_stile_romanico']) && $poi['acf']['rilevanza_stile_romanico']) {
                $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= "</br><p><strong>Rilevanza stile romanico:</strong></p>";
                $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= html_entity_decode($poi['acf']['rilevanza_stile_romanico']);
            }
            if (isset($poi['acf']['come_arrivare']) && $poi['acf']['come_arrivare']) {
                $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= "</br><p><strong>Come arrivare:</strong></p>";
                $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= html_entity_decode($poi['acf']['come_arrivare']);
            }

            if ($this->endpoint == 'https://caiparma.wp.webmapp.it') {
                
                // Adding POI custom parameters from caiparma
                if (isset($poi['acf'])) {
                    $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<h3 style="width: 100%; border-top: 1px solid black; padding: 10px 0;">Informazioni aggiuntive:</h3><table style="border-collapse: collapse; width: 100%; border-style: none;"><tbody>';
                    if (isset($poi['acf']['caipr_poi_localita']) && !empty($poi['acf']['caipr_poi_localita'])) {
                        $this->tags['caipr_poi_localita'] = $poi['acf']['caipr_poi_localita'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Località:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_localita'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_title_alt']) && !empty($poi['acf']['caipr_poi_title_alt'])) {
                        $this->tags['caipr_poi_title_alt'] = $poi['acf']['caipr_poi_title_alt'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Nome alternativo:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_title_alt'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_collegamenti']) && !empty($poi['acf']['caipr_poi_collegamenti'])) {
                        $this->tags['caipr_poi_collegamenti'] = $poi['acf']['caipr_poi_collegamenti'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Collegamenti:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_collegamenti'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_data_opera']) && !empty($poi['acf']['caipr_poi_data_opera'])) {
                        $this->tags['caipr_poi_data_opera'] = $poi['acf']['caipr_poi_data_opera'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Data opera:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_data_opera'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_data_manutenzione']) && !empty($poi['acf']['caipr_poi_data_manutenzione'])) {
                        $this->tags['caipr_poi_data_manutenzione'] = $poi['acf']['caipr_poi_data_manutenzione'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Data manutenzione:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_data_manutenzione'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_data_sopraluogo']) && !empty($poi['acf']['caipr_poi_data_sopraluogo'])) {
                        $this->tags['caipr_poi_data_sopraluogo'] = $poi['acf']['caipr_poi_data_sopraluogo'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Data sopralluogo:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_data_sopraluogo'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_uso_attuale']) && !empty($poi['acf']['caipr_poi_uso_attuale'])) {
                        $this->tags['caipr_poi_uso_attuale'] = $poi['acf']['caipr_poi_uso_attuale'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Uso attuale:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_uso_attuale'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_uso_storico']) && !empty($poi['acf']['caipr_poi_uso_storico'])) {
                        $this->tags['caipr_poi_uso_storico'] = $poi['acf']['caipr_poi_uso_storico'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Uso storico:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_uso_storico'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_epoca']) && !empty($poi['acf']['caipr_poi_epoca'])) {
                        $this->tags['caipr_poi_epoca'] = $poi['acf']['caipr_poi_epoca'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Epoca:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_epoca'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_iconografia']) && !empty($poi['acf']['caipr_poi_iconografia'])) {
                        $this->tags['caipr_poi_iconografia'] = $poi['acf']['caipr_poi_iconografia'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Iconografia:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_iconografia'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_igm_1']) && !empty($poi['acf']['caipr_poi_igm_1'])) {
                        $this->tags['caipr_poi_igm_1'] = $poi['acf']['caipr_poi_igm_1'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">IGM 1° impianto (18811893):</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_igm_1'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_igm_2']) && !empty($poi['acf']['caipr_poi_igm_2'])) {
                        $this->tags['caipr_poi_igm_2'] = $poi['acf']['caipr_poi_igm_2'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">IGM 2° impianto (1936):</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_igm_2'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_proposta_restauro']) && !empty($poi['acf']['caipr_poi_proposta_restauro'])) {
                        $this->tags['caipr_poi_proposta_restauro'] = $poi['acf']['caipr_poi_proposta_restauro'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Proposta di restauro:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_proposta_restauro'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_proprieta']) && !empty($poi['acf']['caipr_poi_proprieta'])) {
                        $this->tags['caipr_poi_proprieta'] = $poi['acf']['caipr_poi_proprieta'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Proprietà:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_proprieta'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_restauratore']) && !empty($poi['acf']['caipr_poi_restauratore'])) {
                        $this->tags['caipr_poi_restauratore'] = $poi['acf']['caipr_poi_restauratore'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Restauratore:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_restauratore'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_rilevatore']) && !empty($poi['acf']['caipr_poi_rilevatore'])) {
                        $this->tags['caipr_poi_rilevatore'] = $poi['acf']['caipr_poi_rilevatore'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Rivelatore:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_rilevatore'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_segnalato_da']) && !empty($poi['acf']['caipr_poi_segnalato_da'])) {
                        $this->tags['caipr_poi_segnalato_da'] = $poi['acf']['caipr_poi_segnalato_da'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Segnalato da:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_segnalato_da'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_gis_er']) && !empty($poi['acf']['caipr_poi_gis_er'])) {
                        $this->tags['caipr_poi_gis_er'] = $poi['acf']['caipr_poi_gis_er'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Segnalato nel WebGis E.R.:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_gis_er'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['caipr_poi_stato_conservazione']) && !empty($poi['acf']['caipr_poi_stato_conservazione'])) {
                        $this->tags['caipr_poi_stato_conservazione'] = $poi['acf']['caipr_poi_stato_conservazione'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Stato conservazione:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['caipr_poi_stato_conservazione'].'</strong></td></tr>';
                    }
                    if (isset($poi['acf']['scheda_elenco']) && !empty($poi['acf']['scheda_elenco'])) {
                        $this->tags['scheda_elenco'] = $poi['acf']['scheda_elenco'];
                        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '<tr><td style="width: 48.6%;">Tipo:</td><td style="width: 48.6%;"><strong>'.$poi['acf']['scheda_elenco'].'</strong></td></tr>';
                    }
                    
                    $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] .= '</tbody></table>';
                }
            }
        }

        $this->tags['excerpt'][explode('_',$poi['wpml_current_locale'])[0]] = html_entity_decode($poi['excerpt']['rendered']);
        if (isset($poi['audio']) && $poi['audio']){
            $this->tags['audio'][explode('_',$poi['wpml_current_locale'])[0]] = $this->uploadAudioAWS($poi['audio']['url'],explode('_',$poi['wpml_current_locale'])[0]);
        }
        if(!empty($poi['wpml_translations'])) {
            foreach($poi['wpml_translations'] as $lang){
                $locale = explode('_',$lang['locale']);
                $this->tags['name'][$locale[0]] = html_entity_decode($lang['post_title']); 
                // Curl request to get the feature translation from external source
                $url = $this->endpoint.'/wp-json/wp/v2/poi/'.$lang['id'];
                $poi_decode = $this->curlRequest($url);
                $this->tags['description'][$locale[0]] = html_entity_decode($poi_decode['content']['rendered']);

                // Adding ACF of Itinera Romanica to description
                if (isset($poi_decode['acf'])){
                    if (isset($poi_decode['acf']['titolo_alternativo']) && $poi_decode['acf']['titolo_alternativo']) {
                        $this->tags['description'][$locale[0]] .= "</br><p><strong>Alternative title:</strong></p>";
                        $this->tags['description'][$locale[0]] .= html_entity_decode($poi_decode['acf']['titolo_alternativo']);
                    }
                    if (isset($poi_decode['acf']['rilevanza_storica']) && $poi_decode['acf']['rilevanza_storica']) {
                        $this->tags['description'][$locale[0]] .= "</br><p><strong>Historical relevance:</strong></p>";
                        $this->tags['description'][$locale[0]] .= html_entity_decode($poi_decode['acf']['rilevanza_storica']);
                    }
                    if (isset($poi_decode['acf']['rilevanza_stile_romanico']) && $poi_decode['acf']['rilevanza_stile_romanico']) {
                        $this->tags['description'][$locale[0]] .= "</br><p><strong>Relevance of the Romanesque style:</strong></p>";
                        $this->tags['description'][$locale[0]] .= html_entity_decode($poi_decode['acf']['rilevanza_stile_romanico']);
                    }
                    if (isset($poi_decode['acf']['come_arrivare']) && $poi_decode['acf']['come_arrivare']) {
                        $this->tags['description'][$locale[0]] .= "</br><p><strong>How to get:</strong></p>";
                        $this->tags['description'][$locale[0]] .= html_entity_decode($poi_decode['acf']['come_arrivare']);
                    }
                }

                $this->tags['excerpt'][$locale[0]] = html_entity_decode($poi_decode['excerpt']['rendered']);

                // Add audio file
                // $domain_path = parse_url($this->endpoint);
                // $this->tags['audio'][$locale[0]] = $this->uploadAudioAWS('https://a.webmapp.it/'.$domain_path['host'].'/media/audios/'.$poi['id'].'_'.$locale[0].'.mp3',$locale[0]);
            }
        }
        // Adding POI parameters of accessibility
        Log::info('Preparing OSF POI ACCESSIBILITY with external ID: '.$this->source_id);
        if (isset($poi['accessibility_validity_date']))
            $this->tags['accessibility_validity_date'] = $poi['accessibility_validity_date'];
        if (isset($poi['accessibility_pdf']) && $poi['accessibility_pdf']) {
            $this->tags['accessibility_pdf'] = $this->uploadPDFtoAWS($poi['accessibility_pdf']['url'],explode('_',$poi['wpml_current_locale'])[0]);
        }
        if (isset($poi['access_mobility_check']))
            $this->tags['access_mobility_check'] = $poi['access_mobility_check'];
        if (isset($poi['access_mobility_level']))
            $this->tags['access_mobility_level'] = $poi['access_mobility_level'];
        if (isset($poi['access_mobility_description']))
            $this->tags['access_mobility_description'] = html_entity_decode($poi['access_mobility_description']);
        if (isset($poi['access_hearing_check']))
            $this->tags['access_hearing_check'] = $poi['access_hearing_check'];
        if (isset($poi['access_hearing_level']))
            $this->tags['access_hearing_level'] = $poi['access_hearing_level'];
        if (isset($poi['access_hearing_description']))
            $this->tags['access_hearing_description'] = html_entity_decode($poi['access_hearing_description']);
        if (isset($poi['access_vision_check']))
            $this->tags['access_vision_check'] = $poi['access_vision_check'];
        if (isset($poi['access_vision_level']))
            $this->tags['access_vision_level'] = $poi['access_vision_level'];
        if (isset($poi['access_vision_description']))
            $this->tags['access_vision_description'] = html_entity_decode($poi['access_vision_description']);
        if (isset($poi['access_cognitive_check']))
            $this->tags['access_cognitive_check'] = $poi['access_cognitive_check'];
        if (isset($poi['access_cognitive_level']))
            $this->tags['access_cognitive_level'] = $poi['access_cognitive_level'];
        if (isset($poi['access_cognitive_description']))
            $this->tags['access_cognitive_description'] = html_entity_decode($poi['access_cognitive_description']);
        if (isset($poi['access_food_check']))
            $this->tags['access_food_check'] = $poi['access_food_check'];
        if (isset($poi['access_food_description']))
            $this->tags['access_food_description'] = html_entity_decode($poi['access_food_description']);
            
        // Adding POI parameters of reachability
        Log::info('Preparing OSF POI REACHABILITY with external ID: '.$this->source_id);
        if (isset($poi['reachability_by_bike_check']))
            $this->tags['reachability_by_bike_check'] = $poi['reachability_by_bike_check'];
        if (isset($poi['reachability_by_bike_description']))
            $this->tags['reachability_by_bike_description'] = html_entity_decode($poi['reachability_by_bike_description']);
        if (isset($poi['reachability_on_foot_check']))
            $this->tags['reachability_on_foot_check'] = $poi['reachability_on_foot_check'];
        if (isset($poi['reachability_on_foot_description']))
            $this->tags['reachability_on_foot_description'] = html_entity_decode($poi['reachability_on_foot_description']);
        if (isset($poi['reachability_by_car_check']))
            $this->tags['reachability_by_car_check'] = $poi['reachability_by_car_check'];
        if (isset($poi['reachability_by_car_description']))
            $this->tags['reachability_by_car_description'] = html_entity_decode($poi['reachability_by_car_description']);
        if (isset($poi['reachability_by_public_transportation_check']))
            $this->tags['reachability_by_public_transportation_check'] = $poi['reachability_by_public_transportation_check'];
        if (isset($poi['reachability_by_public_transportation_description']))
            $this->tags['reachability_by_public_transportation_description'] = html_entity_decode($poi['reachability_by_public_transportation_description']);

        // Adding POI parameters of general info
        Log::info('Preparing OSF POI GENERAL INFO with external ID: '.$this->source_id);
        if (isset($poi['addr:street']))
            $this->tags['addr_street'] = html_entity_decode($poi['addr:street']);
        if (isset($poi['addr:housenumber']))
            $this->tags['addr_housenumber'] = $poi['addr:housenumber'];
        if (isset($poi['addr:postcode']))
            $this->tags['addr_postcode'] = $poi['addr:postcode'];
        if (isset($poi['addr:city']))
            $this->tags['addr_city'] = $poi['addr:city'];
        if (isset($poi['contact:phone']))
            $this->tags['contact_phone'] = $poi['contact:phone'];
        if (isset($poi['contact:email']))
            $this->tags['contact_email'] = $poi['contact:email'];
        if (isset($poi['opening_hours']))
            $this->tags['opening_hours'] = $poi['opening_hours'];
        if (isset($poi['capacity']))
            $this->tags['capacity'] = $poi['capacity'];
        if (isset($poi['stars']))
            $this->tags['stars'] = $poi['stars'];
        } // end of only related urls
        if (isset($poi['n7webmap_rpt_related_url'])) {
            if (is_array($poi['n7webmap_rpt_related_url'])) {
                foreach($poi['n7webmap_rpt_related_url'] as $url) {
                    if (is_array($url)) {
                        $related_url_name = parse_url($url['net7webmap_related_url']);
                        $link = $url['net7webmap_related_url'];
                    } else {
                        $related_url_name = parse_url($url);
                        $link = $url;
                    }
                    $host = $related_url_name;
                    if (isset($related_url_name['host']) && $related_url_name['host']) {
                        $host = $related_url_name['host'];
                    }
                    if (!empty($link) && !empty($host)) {
                        $this->tags['related_url'][$host] = $link;
                    }
                }
            } else {
                $this->tags['related_url'] = $poi['n7webmap_rpt_related_url'];
            }
        }
        if (!$this->only_related_url) { // skip import if only related url is true

        if (isset($poi['ele']))
            $this->tags['ele'] = $poi['ele'];
        if (isset($poi['code']))
            $this->tags['code'] = $poi['code'];
            
        // Adding POI parameters of style
        Log::info('Preparing OSF POI STYLE with external ID: '.$this->source_id);
        if (isset($poi['color']))
            $this->tags['color'] = $poi['color'];
        if (isset($poi['icon']))
            $this->tags['icon'] = $poi['icon'];
        if (isset($poi['noDetails']))
            $this->tags['noDetails'] = $poi['noDetails'];
        if (isset($poi['noInteraction']))
            $this->tags['noInteraction'] = $poi['noInteraction'];
        if (isset($poi['zindex']))
            $this->tags['zindex'] = $poi['zindex'];
        
        // Adding POI custom parameters from caiparma
        if (isset($poi['acf'])) {
            if (isset($poi['acf']['caipr_poi_localita']))
                $this->tags['caipr_poi_localita'] = $poi['acf']['caipr_poi_localita'];
            if (isset($poi['acf']['caipr_poi_title_alt']))
                $this->tags['caipr_poi_title_alt'] = $poi['acf']['caipr_poi_title_alt'];
            if (isset($poi['acf']['caipr_poi_collegamenti']))
                $this->tags['caipr_poi_collegamenti'] = $poi['acf']['caipr_poi_collegamenti'];
            if (isset($poi['acf']['caipr_poi_data_opera']))
                $this->tags['caipr_poi_data_opera'] = $poi['acf']['caipr_poi_data_opera'];
            if (isset($poi['acf']['caipr_poi_data_manutenzione']))
                $this->tags['caipr_poi_data_manutenzione'] = $poi['acf']['caipr_poi_data_manutenzione'];
            if (isset($poi['acf']['caipr_poi_data_sopraluogo']))
                $this->tags['caipr_poi_data_sopraluogo'] = $poi['acf']['caipr_poi_data_sopraluogo'];
            if (isset($poi['acf']['caipr_poi_uso_attuale']))
                $this->tags['caipr_poi_uso_attuale'] = $poi['acf']['caipr_poi_uso_attuale'];
            if (isset($poi['acf']['caipr_poi_uso_storico']))
                $this->tags['caipr_poi_uso_storico'] = $poi['acf']['caipr_poi_uso_storico'];
            if (isset($poi['acf']['caipr_poi_epoca']))
                $this->tags['caipr_poi_epoca'] = $poi['acf']['caipr_poi_epoca'];
            if (isset($poi['acf']['caipr_poi_iconografia']))
                $this->tags['caipr_poi_iconografia'] = $poi['acf']['caipr_poi_iconografia'];
            if (isset($poi['acf']['caipr_poi_igm_1']))
                $this->tags['caipr_poi_igm_1'] = $poi['acf']['caipr_poi_igm_1'];
            if (isset($poi['acf']['caipr_poi_igm_2']))
                $this->tags['caipr_poi_igm_2'] = $poi['acf']['caipr_poi_igm_2'];
            if (isset($poi['acf']['caipr_poi_proposta_restauro']))
                $this->tags['caipr_poi_proposta_restauro'] = $poi['acf']['caipr_poi_proposta_restauro'];
            if (isset($poi['acf']['caipr_poi_proprieta']))
                $this->tags['caipr_poi_proprieta'] = $poi['acf']['caipr_poi_proprieta'];
            if (isset($poi['acf']['caipr_poi_restauratore']))
                $this->tags['caipr_poi_restauratore'] = $poi['acf']['caipr_poi_restauratore'];
            if (isset($poi['acf']['caipr_poi_rilevatore']))
                $this->tags['caipr_poi_rilevatore'] = $poi['acf']['caipr_poi_rilevatore'];
            if (isset($poi['acf']['caipr_poi_segnalato_da']))
                $this->tags['caipr_poi_segnalato_da'] = $poi['acf']['caipr_poi_segnalato_da'];
            if (isset($poi['acf']['caipr_poi_gis_er']))
                $this->tags['caipr_poi_gis_er'] = $poi['acf']['caipr_poi_gis_er'];
            if (isset($poi['acf']['caipr_poi_stato_conservazione']))
                $this->tags['caipr_poi_stato_conservazione'] = $poi['acf']['caipr_poi_stato_conservazione'];
            if (isset($poi['acf']['scheda_elenco']))
                $this->tags['scheda_elenco'] = $poi['acf']['scheda_elenco'];
        }
        
        // Processing the feature image of POI
        if (isset($poi['featured_media']) && $poi['featured_media']) {
            Log::info('Preparing OSF POI FEATURE_IMAGE with external ID: '.$this->source_id);
            $url = $this->endpoint.'/wp-json/wp/v2/media/'.$poi['featured_media'];
            $media = $this->curlRequest($url);
            if ($media) {
                $this->tags['feature_image'] = $this->createOSFMediaFromWP($media);
            } else {
                Log::info('ERROR reaching media: '.$url);
            }
        }
        // Processing the image Gallery of POI
        if (isset($poi['n7webmap_media_gallery']) && $poi['n7webmap_media_gallery']) {
            if (is_array($poi['n7webmap_media_gallery'])){
                Log::info('Preparing OSF POI IMAGE_GALLERY with external ID: '.$this->source_id);
                foreach($poi['n7webmap_media_gallery'] as $img) {
                    $url = $this->endpoint.'/wp-json/wp/v2/media/'.$img['id'];
                    $media = $this->curlRequest($url);
                    if ($media) {
                        $this->tags['image_gallery'][] = $this->createOSFMediaFromWP($media);
                    } else {
                        Log::info('ERROR reaching media: '.$url);
                    }
                }
            }
        }

        // Processing the poi_type
        Log::info('Preparing OSF POI POI_TYPE MAPPING with external ID: '.$this->source_id);
        $path = parse_url($this->endpoint);
        $file_name = str_replace('.','-',$path['host']);
        if (Storage::disk('mapping')->exists($file_name.'.json')) {
            $taxonomy_map = Storage::disk('mapping')->get($file_name.'.json');

            if (!empty(json_decode($taxonomy_map,true)['poi_type']) && $poi['webmapp_category']) {
                foreach ($poi['webmapp_category'] as $tax) {
                    $this->tags['poi_type'][] = json_decode($taxonomy_map,true)['poi_type'][$tax]['geohub_identifier'];
                }
            }
        }
        } // end import only related url if is true
    }

    /**
     * It populates the tags variable of media so that it can be syncronized with EcMedia
     * 
     * @param array $media The OutSourceFeature parameters to be added or updated 
     * 
     */
    public function prepareMediaTagsJson($media){ 
        Log::info('Preparing OSF MEDIA TRANSLATIONS with external ID: '.$media['id']);
        $tags = [];
        if(!empty($media['wpml_current_locale'])) { 
            $local_lang = explode('_',$media['wpml_current_locale'])[0];
        } else {
            $local_lang = 'it';
        }
        $tags['name'][$local_lang] = $media['title']['rendered'];
        $tags['description'][$local_lang] = $media['caption']['rendered'];
        if(!empty($media['wpml_translations'])) {
            foreach($media['wpml_translations'] as $lang){
                $locale = explode('_',$lang['locale']);
                $tags['name'][$locale[0]] = $lang['post_title']; 
                // Curl request to get the feature translation from external source
                // $url = $this->endpoint.'/wp-json/wp/v2/media/'.$lang['id'];
                // $media_decode = $this->curlRequest($url);
                // $tags['description'][$locale[0]] = $media_decode['caption']['rendered'];
            }
        }

        try{
            // Saving the Media in to the s3-osfmedia storage (.env in production)
            $storage_name = config('geohub.osf_media_storage_name');
            Log::info('Saving OSF MEDIA on storage '.$storage_name);
            Log::info(" ");
            if (isset($media['media_details']) && isset($media['media_details']['file'])) {
                $wp_url = $this->endpoint.'/wp-content/uploads/'.$media['media_details']['file'];
            } elseif (isset($media['guid'])) {
                $wp_url = $media['media_details']['rendered'];
            } else {
                $wp_url = $media['source_url'];
            }
            Log::info('Geting image from url: '.$wp_url);
            $url_encoded = preg_replace_callback('/[^\x20-\x7f]/', function($match) {
                return urlencode($match[0]);
            }, $wp_url);
            $contents = file_get_contents($url_encoded);
            $basename = explode('.',basename($wp_url));
            $s3_osfmedia = Storage::disk($storage_name);
            $osf_name_tmp = sha1($basename[0]) . '.' . $basename[1];
            $s3_osfmedia->put($osf_name_tmp, $contents);

            Log::info('Saved OSF Media with name: '.$osf_name_tmp);
            $tags['url'] = ($s3_osfmedia->exists($osf_name_tmp))?$osf_name_tmp:'';
        } catch(Exception $e) {
            echo $e;
            Log::info('Saving media in s3-osfmedia error:' . $e);
        }

        return $tags;
    }
}