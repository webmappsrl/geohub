<?php

namespace App\Classes\OutSourceImporter;

use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Routing\Exception\InvalidParameterException;

abstract class OutSourceImporterListAbstract
{

    // DATA
    protected $type;
    protected $endpoint;

    /**
     * It sets all needed properties in order to perform the import
     * 
     *
     * @param string $type the of the feature (Track, Poi or Media)
     * @param string $endpoint the url from which import begins (https://stelvio.wp.webmapp.it)
     */
    public function __construct(string $type, string  $endpoint) 
    {
        $this->type = strtolower($type);
        $this->endpoint = strtolower($endpoint);
    }

    abstract protected function getTrackList();
    abstract protected function getPoiList();
    abstract protected function getMediaList();

    public function getList() {
        switch ($this->type) {
            case 'track':
                return $this->getTrackList();
                break;
            
            case 'poi':
                return $this->getPoiList();
                break;
            
            case 'media':
                return $this->getMediaList();
                break;
            
            default:
                return null;
                break;
        }
    }
}