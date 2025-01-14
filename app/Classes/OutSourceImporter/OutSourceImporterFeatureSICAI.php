<?php

namespace App\Classes\OutSourceImporter;

use App\Models\OutSourceFeature;
use App\Traits\ImporterAndSyncTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OutSourceImporterFeatureSICAI extends OutSourceImporterFeatureAbstract
{
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
    public function importTrack()
    {
        $error_not_created = [];
        try {
            // DB connection to get the feature information from external source
            $db = DB::connection('out_source_sicai');
            $track = $db->table('sentiero_italia.SI_Tappe')
                ->where('id_2', $this->source_id)
                ->first();

            // prepare feature parameters to pass to updateOrCreate function
            Log::info('Preparing OSF Track with external ID: '.$this->source_id);
            $geometry = DB::select("SELECT ST_AsText(ST_LineMerge(ST_Transform(Geometry('$track->geom'),4326)))")[0]->st_astext;
            $this->mediaGeom = DB::select("SELECT ST_AsText(ST_StartPoint(ST_LineMerge(ST_Transform(Geometry('$track->geom'),4326))))")[0]->st_astext;
            $this->params['geometry'] = $geometry;
            $this->params['provider'] = get_class($this);
            $this->params['type'] = $this->type;
            $this->params['raw_data'] = json_encode($track);

            // prepare the value of tags data
            Log::info('Preparing OSF Track TAGS with external ID: '.$this->source_id);
            $this->prepareTrackTagsJson($track, $geometry);
            $this->params['tags'] = $this->tags;
            Log::info('Finished preparing OSF Track with external ID: '.$this->source_id);
            Log::info('Starting creating OSF Track with external ID: '.$this->source_id);

            return $this->create_or_update_feature($this->params);
        } catch (Exception $e) {
            array_push($error_not_created, $track->id_0);
            Log::info('Error creating EcPoi from OSF with id: '.$this->source_id."\n ERROR: ".$e->getMessage());
        }
        if ($error_not_created) {
            Log::info('Ec features not created from Source with osf ID: ');
            foreach ($error_not_created as $id) {
                Log::info($id);
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
        $error_not_created = [];
        try {
            // DB connection to get the feature information from external source
            $db = DB::connection('out_source_sicai');
            $poi = $db->table('sentiero_italia.pt_accoglienza_unofficial')
                ->where('id_0', $this->source_id)
                ->first();

            // prepare feature parameters to pass to updateOrCreate function
            Log::info('Preparing OSF poi with external ID: '.$this->source_id);
            $geometry_poi = DB::select("SELECT ST_Transform(Geometry('$poi->geom'),4326) As g")[0]->g;
            $this->params['geometry'] = $geometry_poi;
            $this->mediaGeom = $geometry_poi;
            $this->params['provider'] = get_class($this);
            $this->params['type'] = $this->type;
            $this->params['raw_data'] = json_encode($poi);

            // prepare the value of tags data
            Log::info('Preparing OSF poi TAGS with external ID: '.$this->source_id);
            $this->preparepoiTagsJson($poi);
            $this->params['tags'] = $this->tags;
            Log::info('Finished preparing OSF poi with external ID: '.$this->source_id);
            Log::info('Starting creating OSF poi with external ID: '.$this->source_id);

            return $this->create_or_update_feature($this->params);
        } catch (Exception $e) {
            array_push($error_not_created, $poi->id_0);
            Log::info('Error creating EcPoi from OSF with id: '.$this->source_id."\n ERROR: ".$e->getMessage());
        }
        if ($error_not_created) {
            Log::info('Ec features not created from Source with osf ID: ');
            foreach ($error_not_created as $id) {
                Log::info($id);
            }
        }
    }

    public function importMedia()
    {
        return 'getMediaList result';
    }

    /**
     * It updateOrCreate method of the class OutSourceFeature
     *
     * @param  array  $params  The OutSourceFeature parameters to be added or updated
     * @return int The ID of OutSourceFeature created
     */
    protected function create_or_update_feature(array $params)
    {

        $feature = OutSourceFeature::updateOrCreate(
            [
                'source_id' => $this->source_id,
                'endpoint' => $this->endpoint,
            ],
            $params);

        return $feature->id;
    }

    /**
     * It populates the tags variable with the track curl information so that it can be syncronized with EcTrack
     *
     * @param  object  $track  The OutSourceFeature parameters to be added or updated
     */
    protected function prepareTrackTagsJson($track, $geometry)
    {
        Log::info('Preparing OSF Track TRANSLATIONS with external ID: '.$this->source_id);
        $this->tags['name']['it'] = $track->tappa;

        $this->tags['description']['it'] = '<strong>Percorribilità</strong>:</br>';
        if ($track->percorribilità == 'Tutta percorribile') {
            $this->tags['description']['it'] .= $track->percorribilità.'<br>';
        } elseif ($track->percorribilità == 'Percorribile in parte') {
            $this->tags['description']['it'] .= $track->percorribilità.'<br>';
        } else {
            $this->tags['description']['it'] .= 'Dato in aggiornamento'.'<br>';
        }

        $this->tags['description']['it'] .= '<strong>Segnaletica</strong>:</br>';
        if ($track->segnaletica == 'La tappa è tutta segnata') {
            $this->tags['description']['it'] .= $track->segnaletica.'<br>';
        } elseif ($track->segnaletica == 'La tappa è segnata solo in parte') {
            $this->tags['description']['it'] .= $track->segnaletica.'<br>';
        } elseif ($track->segnaletica == 'La tappa non è segnata') {
            $this->tags['description']['it'] .= $track->segnaletica.'<br>';
        } else {
            $this->tags['description']['it'] .= 'Dato in aggiornamento'.'<br>';
        }

        if (! empty($track->Note)) {
            $this->tags['description']['it'] .= '<strong>Note</strong>:</br>';
            $this->tags['description']['it'] .= $track->Note.'<br>';
        }
        if ($track->descrizione_sito) {
            $this->tags['description']['it'] .= '<strong>Descrizione</strong>:</br>';
            $this->tags['description']['it'] .= $track->descrizione_sito;
        }
        if ($track->partenza) {
            $this->tags['from'] = $track->partenza;
        }
        if ($track->arrivo) {
            $this->tags['to'] = $track->arrivo;
        }
        if ($track->difficolta && $track->difficolta !== 'Dato in aggiornamento') {
            $this->tags['cai_scale'] = $track->difficolta;
        }
        // if ($track->percorribilità && $track->percorribilità == 'Non percorribile') {
        //     $this->tags['not_accessible'] = true;
        // }

        if ($geometry) {
            $related_pois = DB::select("SELECT id from out_source_features WHERE type='poi' and endpoint='sicai_pt_accoglienza_unofficial' and ST_Contains(ST_BUFFER(ST_SetSRID(ST_GeomFromText('$geometry'),4326),0.01, 'endcap=round join=round'),geometry::geometry);");

            if (is_array($related_pois) && ! empty($related_pois)) {
                foreach ($related_pois as $poi) {
                    $this->tags['related_poi'][] = $poi->id;
                }
            }
        }

        // Processing the feature image of Track
        if (isset($track->immagine) && $track->immagine) {
            Log::info('Preparing OSF track FEATURE_IMAGE with external track ID: '.$this->source_id);

            $this->tags['feature_image'] = $this->createOSFMedia($track->immagine, $track, 000);
        }
    }

    /**
     * It populates the tags variable with the POI curl information so that it can be syncronized with EcPOI
     *
     * @param  array  $poi  The OutSourceFeature parameters to be added or updated
     */
    protected function preparePOITagsJson($poi)
    {
        $poi = json_decode(json_encode($poi), true);
        Log::info('Preparing OSF POI TRANSLATIONS with external ID: '.$this->source_id);
        $this->tags['name']['it'] = $poi['name'];
        if (! empty($poi['Descrizione'])) {
            $this->tags['description']['it'] = $poi['Descrizione'];
        }

        // Adding POI parameters of general info
        Log::info('Preparing OSF POI GENERAL INFO with external ID: '.$this->source_id);
        if (isset($poi['addr:street'])) {
            $this->tags['addr_street'] = html_entity_decode($poi['addr:street']);
        }
        if (isset($poi['addr:housenumber'])) {
            $this->tags['addr_housenumber'] = $poi['addr:housenumber'];
        }
        if (isset($poi['addr:city'])) {
            $this->tags['addr_city'] = $poi['addr:city'];
        }
        if (isset($poi['phone'])) {
            $this->tags['contact_phone'] = $poi['phone'];
        }
        if (isset($poi['email'])) {
            $this->tags['contact_email'] = $poi['email'];
        }
        if (isset($poi['opening_hours'])) {
            $this->tags['opening_hours'] = $poi['opening_hours'];
        }
        if (isset($poi['website'])) {
            $related_url_name = parse_url($poi['website']);
            $host = $poi['website'];
            if (isset($related_url_name['host']) && $related_url_name['host']) {
                $host = $related_url_name['host'];
            }
            $this->tags['related_url'][$host] = $poi['website'];
        }

        // Processing the feature image of POI
        if (isset($poi['immagine']) && $poi['immagine']) {
            Log::info('Preparing OSF POI FEATURE_IMAGE with external POI ID: '.$this->source_id);

            $this->tags['feature_image'] = $this->createOSFMedia($poi['immagine'], $poi, 000);
        }

        // Processing the gallery image of POI
        if (isset($poi['foto02']) && $poi['foto02']) {
            Log::info('Preparing OSF POI GALLERY foto02 with external POI ID: '.$this->source_id);

            $this->tags['image_gallery'][] = $this->createOSFMedia($poi['foto02'], $poi, 001);
        }

        // Processing the gallery image of POI
        if (isset($poi['foto03']) && $poi['foto03']) {
            Log::info('Preparing OSF POI GALLERY foto03 with external POI ID: '.$this->source_id);

            $this->tags['image_gallery'][] = $this->createOSFMedia($poi['foto03'], $poi, 003);
        }

        // Processing the gallery image of POI
        if (isset($poi['foto04']) && $poi['foto04']) {
            Log::info('Preparing OSF POI GALLERY foto04 with external POI ID: '.$this->source_id);

            $this->tags['image_gallery'][] = $this->createOSFMedia($poi['foto04'], $poi, 004);
        }

        // Processing the gallery image of POI
        if (isset($poi['foto05']) && $poi['foto05']) {
            Log::info('Preparing OSF POI GALLERY foto05 with external POI ID: '.$this->source_id);

            $this->tags['image_gallery'][] = $this->createOSFMedia($poi['foto05'], $poi, 005);
        }

        // Processing the poi_type
        Log::info('Preparing OSF POI POI_TYPE MAPPING with external ID: '.$this->source_id);
        if (isset($poi['tourism']) && $poi['tourism']) {
            $this->tags['poi_type'][] = $poi['tourism'];
        }
    }

    /**
     * It populates the tags variable of media so that it can be syncronized with EcMedia
     *
     * @param  array  $media  The OutSourceFeature parameters to be added or updated
     */
    public function createOSFMedia($image, $item, $suffix)
    {
        try {
            $base = 'https://sentieroitaliamappe.cai.it/index.php/view/media/getMedia?repository=sicaipubblico&project=SICAI_Pubblico&path=';
            $item = json_decode(json_encode($item), true);

            $image = explode(',', $image)[0];

            if (isset($item['name']) && $item['name']) {
                $tags['name']['it'] = $item['name'];
            } else {
                $tags['name']['it'] = $item['tappa'];
            }
            if (isset($item['id_0']) && $item['id_0']) {
                $item_id = $item['id_0'];
            } else {
                $item_id = $item['id_2'];
            }
            Log::info('Preparing OSF MEDIA TAGS with external ID: '.$item_id);
            // Saving the Media in to the s3-osfmedia storage
            $storage_name = config('geohub.osf_media_storage_name');
            Log::info('Saving OSF MEDIA on storage '.$storage_name);
            Log::info(' ');
            Log::info('Geting image from url: '.$base.$image);
            $url_encoded = rawurlencode($image);
            $contents = file_get_contents($base.$url_encoded);
            $basename = explode('.', basename($image));
            $s3_osfmedia = Storage::disk($storage_name);
            $osf_name_tmp = sha1($basename[0]).'.'.$basename[1];
            $s3_osfmedia->put($osf_name_tmp, $contents);

            Log::info('Saved OSF Media with name: '.$osf_name_tmp);
            $tags['url'] = ($s3_osfmedia->exists($osf_name_tmp)) ? $osf_name_tmp : '';

            Log::info('Preparing OSF MEDIA TAGS with external ID: '.$item_id);
            $params['tags'] = $tags;
            $params['type'] = 'media';
            $params['provider'] = get_class($this);
            $params['geometry'] = $this->mediaGeom;
            Log::info('Finished preparing OSF MEDIA with external ID: '.$item_id);
            Log::info('Starting creating OSF MEDIA with external ID: '.$item_id);
            $feature = OutSourceFeature::updateOrCreate(
                [
                    'source_id' => $item_id.$suffix,
                    'endpoint' => $this->endpoint,
                ], $params);

            return $feature->id;
        } catch (Exception $e) {
            echo $e;
            Log::info('Saving media in s3-osfmedia error:'.$e);

            return null;
        }

        return null;
    }
}
