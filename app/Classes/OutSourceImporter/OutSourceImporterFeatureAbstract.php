<?php

namespace App\Classes\OutSourceImporter;

abstract class OutSourceImporterFeatureAbstract
{
    // DATA
    protected $type;

    protected $endpoint;

    protected $source_id;

    protected $only_related_url;

    /**
     * It sets all needed properties in order to perform the import in the out_source_feature table
     *
     *
     * @param  string  $type  the of the feature (Track, Poi or Media)
     * @param  string  $endpoint  the url from which import begins (https://stelvio.wp.webmapp.it)
     * @param  string  $source_id  the id of the feature being imported
     * @param  bool  $only_related_url  true if only import related url value
     */
    public function __construct(string $type, string $endpoint, string $source_id, bool $only_related_url = false)
    {
        $this->type = strtolower($type);
        $this->endpoint = $endpoint;
        $this->source_id = $source_id;
        $this->only_related_url = $only_related_url;
    }

    abstract protected function importTrack();

    abstract protected function importPoi();

    abstract protected function importMedia();

    public function importFeature()
    {
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

    /**
     * TODO:
     * 1. If OSF does not exist return true
     *
     * @param  string  $date  'YYYY-MM-DD HH:MM:SS'
     */
    public function needToBeUdated(string $date): bool
    {
        return true;
    }
}
