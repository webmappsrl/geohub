<?php

namespace App\Traits;

use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

trait ElasticIndexTrait
{
    public function getClient()
    {
        $host = config('services.elastic.host');
        $username = config('services.elastic.username');
        $password = config('services.elastic.password');
        try {
            $client = ClientBuilder::create()
                ->setHosts([$host])
                ->setBasicAuthentication($username, $password)
                ->setSSLVerification(false)
                ->build();

            if ($client->ping()) {
                Log::info('Connection to Elasticsearch successful');
            } else {
                Log::error('Connection to Elasticsearch failed: No ping response');
            }

            return $client;
        } catch (\Exception $e) {
            Log::error('Connection to Elasticsearch failed: '.$e->getMessage());
        }
    }

    public function createElasticIndex($indexName)
    {
        try {
            // Configurazione del client ElasticSearch
            $client = $this->getClient();

            // Parametri per la creazione dell'indice
            $params = [
                'index' => $indexName,
                'body' => [
                    'mappings' => [
                        'properties' => [
                            'name' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    'settings' => [
                        'max_result_window' => 50000,
                    ],
                ],
            ];

            // Creazione dell'indice

            if (! $client->indices()->exists(['index' => $indexName])) {
                $response = $client->indices()->create($params);
                Log::info('Indice creato con successo: '.json_encode($response));
            } else {
                Log::info('Indice già esistente');
            }
        } catch (\Exception $e) {
            Log::error('Errore nella creazione dell\'indice: '.$e->getMessage());
            throw $e; // Rilancia l'eccezione se necessario
        }
    }

    public function deleteElasticIndex($indexName)
    {
        try {
            $client = $this->getClient();
            Log::info('ping:'.$client->ping());
            // Verifica se l'indice esiste prima di tentare di cancellarlo
            if ($client->indices()->exists(['index' => $indexName])) {
                $response = $client->indices()->delete(['index' => $indexName]);
                Log::info("Indice '$indexName' cancellato con successo.");

                return response()->json(['status' => 'success', 'message' => "Indice '$indexName' cancellato con successo."], 200);
            } else {
                Log::warning("Indice '$indexName' non esiste.");

                return response()->json(['status' => 'error', 'message' => "Indice '$indexName' non esiste."], 404);
            }
        } catch (\Exception $e) {
            Log::error('Errore durante la cancellazione dell\'indice: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Errore durante la cancellazione dell\'indice.'], 500);
        }
    }

    public function deleteElasticIndexDoc($indexName, $id)
    {
        try {
            $client = $this->getClient();

            // Parametri per la cancellazione dell'indice
            $params = [
                'index' => $indexName,
                'id' => $id,
            ];
            if ($client->exists($params)) {
                Log::info('ElasticIndexTrait => deleteElasticIndexDoc:  '.$params['index'].' doc'.$params['id']);
                $response = $client->delete($params);
                Log::info($response);
            }
        } catch (\Exception $e) {
            Log::error('ElasticIndexTrait => deleteElasticIndexDoc: '.$e->getMessage());
            throw $e;
        }
    }

    public function elasticIndexDoc($indexName, $id, $doc)
    {
        ini_set('memory_limit', '1024M');

        try {
            $client = $this->getClient();

            // Parametri per la cancellazione dell'indice
            $params = [
                'index' => $indexName,
                'id' => $id,
                'body' => [
                    'doc' => $doc,
                    'doc_as_upsert' => true, // Crea il documento se non esiste (upsert)
                ],
            ];
            // Indicizza o aggiorna il documento
            $response = $client->index($params);
            Log::info("Documento con ID '$id' indicizzato/aggiornato con successo nell'indice '$indexName'.");

            return response()->json(['status' => 'success', 'message' => 'Documento indicizzato/aggiornato con successo.', 'data' => $response], 200);
        } catch (\Exception $e) {
            Log::error('ElasticIndexTrait => updateElasticIndexDoc: '.json_encode($doc));
            throw $e;
        }
    }
}
