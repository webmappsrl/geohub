<?php

namespace App\Observers;

use App\Models\EcTrack;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class EcTrackElasticObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the EcTrack "created" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function created(EcTrack $ecTrack)
    {
    }

    /**
     * Handle the EcTrack "updated" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function updated(EcTrack $ecTrack)
    {
        #REF: https://github.com/elastic/elasticsearch-php/
        #REF: https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html

        //$hosts = ['https://forge:1b0VUJxRFxeOupkjPeie@elastic.sis-te.com'];

        // $host = env('ELASTIC_HTTP_HOST');
        // $hosts = [$host];
        // $client = ClientBuilder::create()->setHosts($hosts)->build();

        $ecTrackLayers = $ecTrack->getLayersByApp();
        if (!empty($ecTrackLayers)) {
            foreach ($ecTrackLayers as $app_id => $layer_ids) {
                if (!empty($layer_ids)) {
                    // $ecTrack->elasticIndex('app_' . $app_id, $layer_ids, 'PUT');
                    $ecTrack->elasticIndexUpsert('app_' . $app_id, $layer_ids);
                } else {
                    $ecTrack->elasticIndexDelete('app_' . $app_id);
                }
            }
        }      
    }

    /**
     * Handle the EcTrack "deleted" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function deleted(EcTrack $ecTrack)
    {
        //
    }

    /**
     * Handle the EcTrack "restored" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function restored(EcTrack $ecTrack)
    {
        //
    }

    /**
     * Handle the EcTrack "force deleted" event.
     *
     * @param  \App\Models\EcTrack  $ecTrack
     * @return void
     */
    public function forceDeleted(EcTrack $ecTrack)
    {
        //
    }
}
