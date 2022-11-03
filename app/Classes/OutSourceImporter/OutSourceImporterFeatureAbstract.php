<?php

namespace App\Classes\OutSourceImporter;

use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Routing\Exception\InvalidParameterException;

abstract class OutSourceImporterFeatureAbstract
{

    // DATA
    protected $type;
    protected $endpoint;
    protected $source_id;

    /**
     * It sets all needed properties in order to perform the import in the out_source_feature table
     * 
     *
     * @param string $type the of the feature (Track, Poi or Media)
     * @param string $endpoint the url from which import begins (https://stelvio.wp.webmapp.it)
     * @param string $source_id the id of the feature being imported
     */
    public function __construct(string $type, string $endpoint, string $source_id, $only_related_url) 
    {
        $this->type = strtolower($type);
        $this->endpoint = $endpoint;
        $this->source_id = $source_id;
        $this->only_related_url = $only_related_url;
    }

    abstract protected function importTrack();
    abstract protected function importPoi();
    abstract protected function importMedia();

    public function importFeature() {
        switch ($this->type) {
            case 'track':
                return $this->importTrack();
                break;
            
            case 'poi':
                return $this->importPoi();
                break;
            
            case 'media':
                return $this->importMedia();
                break;
            
            default:
                return null;
                break;
        }
    }
}