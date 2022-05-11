<?php

namespace App\Classes\EcSynchronizer;

use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Routing\Exception\InvalidParameterException;

class SyncEcFromOutSource
{
    // DATA
    protected $type;
    protected $author;
    protected $endpoint;

    /**
     * It sets all needed properties in order to perform the sync ec_tracks table from out_source_features
     * 
     *
     * @param string $type the of the feature (Track, Poi or Media)
     * @param string $endpoint the url from which import begins (https://stelvio.wp.webmapp.it)
     * @param string $author the email of the author to be associated with features
     */
    public function __construct(string $type, string $author) 
    {
        $this->type = strtolower($type);
        $this->author = strtolower($author);
    }
}