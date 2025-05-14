<?php

namespace App\Classes\OutSourceImporter;

use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;

abstract class OutSourceImporterListAbstract
{
    // DATA
    protected $type;

    protected $endpoint;

    protected $logChannel;

    /**
     * It sets all needed properties in order to perform the import
     *
     *
     * @param  string  $type  the of the feature (Track, Poi or Media)
     * @param  string  $endpoint  the url from which import begins (https://stelvio.wp.webmapp.it)
     * @param  Logger  $logChannel  the log channel to use for logging
     */
    public function __construct(string $type, string $endpoint, Logger $logChannel)
    {
        $this->type = strtolower($type);
        $this->endpoint = strtolower($endpoint);
        $this->logChannel = $logChannel;
    }

    abstract protected function getTrackList(): array;

    abstract protected function getPoiList(): array;

    abstract protected function getMediaList(): array;

    /**
     * It returns the features list of a specific data provider as hash (id=>last_modified)
     *
     * @return array ['id1'=>'YYYY-MM-AA HH:MM:SS','id2'=>'YYYY-MM-AA HH:MM:SS',...]
     */
    public function getList(): array
    {
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
                return [];
                break;
        }
    }
}
