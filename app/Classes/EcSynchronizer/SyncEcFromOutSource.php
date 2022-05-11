<?php

namespace App\Classes\EcSynchronizer;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Routing\Exception\InvalidParameterException;

class SyncEcFromOutSource
{
    // DATA
    protected $type;
    protected $author;
    protected $endpoint;
    protected $provider;
    protected $app;
    protected $name_format;
    protected $activity;

    /**
     * It sets all needed properties in order to perform the sync ec_tracks table from out_source_features
     * 
     *
     * @param string $type the of the feature (Track, Poi or Media)
     * @param string $endpoint the url from which import begins (https://stelvio.wp.webmapp.it)
     * @param string $author the email of the author to be associated with features
     */
    public function __construct(string $type, string $author, string $provider = '', string $endpoint = '',string $activity = 'hiking' ,string $name_format, int $app = 0) 
    {
        if (is_numeric($author)) {
            try {
                $user = User::find(intval($author));
                $this->author = $user->id;
            } catch (Exception $e) {
                throw new Exception('No User found'); 
            }
        } else {
            try {
                $user = User::where('email',strtolower($author))->first();
                $this->author = $user->id;
            } catch (Exception $e) {
                throw new Exception('No User found'); 
            }
        }

        if (strtolower($type) == 'track' ||
            strtolower($type) == 'poi' || 
            strtolower($type) == 'media' || 
            strtolower($type) == 'taxonomy' 
            ) {
                $this->type = strtolower($type);
            } else {
                throw new Exception('The value of parameter type is not currect'); 
            }

        $this->provider = $provider;            
        $this->endpoint = $endpoint;            
        $this->activity = $activity;            
        $this->name_format = $name_format;            
        $this->app = $app;            
    }
}