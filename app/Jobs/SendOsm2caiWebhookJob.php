<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendOsm2caiWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minuti di timeout
    public $tries = 3; // Riprova 3 volte

    private array $requestData;
    private int $appId;
    private ?string $uuid;
    private string $ugcType;
    private array $files;
    private array $headers;
    private ?string $userEmail;

    /**
     * Create a new job instance.
     */
    public function __construct(array $requestData, int $appId, ?string $uuid = null, string $ugcType = 'poi', array $files = [], array $headers = [], ?string $userEmail = null)
    {
        $this->requestData = $requestData;
        $this->appId = $appId;
        $this->uuid = $uuid;
        $this->ugcType = $ugcType;
        $this->files = $files;
        $this->headers = $headers;
        $this->userEmail = $userEmail;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info("🚀 SendOsm2caiWebhookJob: Inizio job", [
            'app_id' => $this->appId,
            'uuid' => $this->uuid,
            'ugc_type' => $this->ugcType,
            'files_count' => count($this->files)
        ]);

        // Delay di 30 secondi per aspettare il salvataggio dell'UGC POI
        Log::info("⏳ SendOsm2caiWebhookJob: Inizio delay di 30 secondi", [
            'app_id' => $this->appId,
            'uuid' => $this->uuid
        ]);

        sleep(30);

        Log::info("✅ SendOsm2caiWebhookJob: Delay completato", [
            'app_id' => $this->appId,
            'uuid' => $this->uuid
        ]);

        // Stampa gli ultimi 10 UGC POI creati
        $this->logLastUgcPois();

        // Cerca l'UGC POI usando l'UUID
        $geohubId = null;
        if ($this->uuid) {
            $geohubId = $this->findUgcByUuid($this->uuid, $this->appId);
            if ($geohubId) {
                Log::info("✅ SendOsm2caiWebhookJob: UGC POI trovato", [
                    'uuid' => $this->uuid,
                    'geohub_id' => $geohubId,
                    'app_id' => $this->appId
                ]);
            } else {
                Log::warning("⚠️ SendOsm2caiWebhookJob: UGC POI non trovato", [
                    'uuid' => $this->uuid,
                    'app_id' => $this->appId
                ]);
            }
        }

        // Invia il webhook
        $this->sendWebhook($geohubId);

        // Pulisci i file temporanei
        $this->cleanupTempFiles();
    }

    /**
     * Cerca l'UGC tramite UUID e restituisce l'ID di Geohub
     */
    private function findUgcByUuid(string $uuid, int $appId): ?int
    {
        try {
            // Cerca nei modelli appropriati
            if ($this->ugcType === 'poi') {
                $ugc = \App\Models\UgcPoi::where('app_id', $appId)
                    ->whereJsonContains('properties->uuid', $uuid)
                    ->first();
            } elseif ($this->ugcType === 'track') {
                $ugc = \App\Models\UgcTrack::where('app_id', $appId)
                    ->whereJsonContains('properties->uuid', $uuid)
                    ->first();
            } else {
                // Fallback: cerca in tutti i modelli UGC
                $ugc = \App\Models\UgcPoi::where('app_id', $appId)
                    ->whereJsonContains('properties->uuid', $uuid)
                    ->first();

                if (!$ugc) {
                    $ugc = \App\Models\UgcTrack::where('app_id', $appId)
                        ->whereJsonContains('properties->uuid', $uuid)
                        ->first();
                }
            }

            return $ugc ? $ugc->id : null;
        } catch (\Exception $e) {
            Log::error("❌ SendOsm2caiWebhookJob: Errore nella ricerca UGC tramite UUID", [
                'uuid' => $uuid,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Stampa gli ultimi 10 UGC POI creati con UUID e geohub_id
     */
    private function logLastUgcPois(): void
    {
        try {
            $lastPois = \App\Models\UgcPoi::orderBy('created_at', 'desc')
                ->limit(10)
                ->get(['id', 'properties', 'created_at']);

            $poiList = [];
            foreach ($lastPois as $poi) {
                $uuid = null;
                $properties = $poi->properties;

                // Estrai UUID dalle properties
                if (is_array($properties) && isset($properties['uuid'])) {
                    $uuid = $properties['uuid'];
                } elseif (is_string($properties)) {
                    $decodedProperties = json_decode($properties, true);
                    if (isset($decodedProperties['uuid'])) {
                        $uuid = $decodedProperties['uuid'];
                    }
                }

                $poiList[] = [
                    'geohub_id' => $poi->id,
                    'uuid' => $uuid,
                    'created_at' => $poi->created_at->format('Y-m-d H:i:s')
                ];
            }

            Log::info("📋 SendOsm2caiWebhookJob: Ultimi 10 UGC POI creati", [
                'poi_list' => $poiList,
                'total_count' => count($poiList)
            ]);
        } catch (\Exception $e) {
            Log::error("❌ SendOsm2caiWebhookJob: Errore nel recupero ultimi UGC POI", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invia il webhook a osm2cai
     */
    private function sendWebhook(?int $geohubId): void
    {
        // URL del webhook di osm2cai2
        $webhookUrl = 'https://osm2cai2.dev.maphub.it/api/webhook/ugc/' . $this->ugcType;

        // Prepara i dati multipart
        $multipartFields = $this->prepareMultipartWebhookData($geohubId);

        Log::info("📤 SendOsm2caiWebhookJob: Invio webhook multipart", [
            'webhook_url' => $webhookUrl,
            'ugc_type' => $this->ugcType,
            'action' => 'create',
            'fields_count' => count($multipartFields),
            'has_images' => collect($multipartFields)->contains('name', 'images[0]'),
            'geohub_id' => $geohubId
        ]);

        try {
            // Prepara headers per il webhook multipart
            $webhookHeaders = [
                'Accept' => $this->headers['Accept'] ?? 'application/json',
                'User-Agent' => $this->headers['User-Agent'] ?? 'Geohub-Webhook/1.0',
                'X-Geohub-Redirect' => 'true',
                'X-Geohub-Source' => 'geohub',
                'X-Geohub-Original-App-Id' => $this->appId
            ];

            // Aggiungi l'header di autorizzazione se presente
            if (!empty($this->headers['Authorization'])) {
                $webhookHeaders['Authorization'] = $this->headers['Authorization'];
            }

            // Aggiungi l'email dell'utente se disponibile
            if ($this->userEmail) {
                $webhookHeaders['X-Geohub-User-Email'] = $this->userEmail;
                Log::info("📧 SendOsm2caiWebhookJob: Email utente aggiunta al webhook", [
                    'email' => $this->userEmail
                ]);
            } else {
                Log::info("📧 SendOsm2caiWebhookJob: Nessuna email utente disponibile");
            }

            // Invia la richiesta multipart
            $response = Http::timeout(30)
                ->withHeaders($webhookHeaders)
                ->asMultipart()
                ->post($webhookUrl, $multipartFields);

            Log::info("📥 SendOsm2caiWebhookJob: Risposta webhook multipart", [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'success' => $response->successful()
            ]);
        } catch (\Exception $e) {
            Log::error("❌ SendOsm2caiWebhookJob: Errore webhook multipart", [
                'error' => $e->getMessage(),
                'app_id' => $this->appId
            ]);
            throw $e; // Rilancia l'eccezione per il retry
        }
    }

    /**
     * Prepara i dati per il webhook multipart
     */
    private function prepareMultipartWebhookData(?int $geohubId): array
    {
        // Prepara il feature dai dati della request
        $feature = $this->extractFeatureFromRequestData();

        // Mappa l'app_id da geohub a osm2cai
        $mappedAppId = $this->mapAppId($this->appId);
        if (!isset($feature['properties']['app_id'])) {
            $feature['properties']['app_id'] = $mappedAppId;
        } else {
            $feature['properties']['app_id'] = $mappedAppId;
        }

        // Aggiungi il geohub_id se trovato
        if ($geohubId) {
            $feature['properties']['geohub_id'] = $geohubId;
        }

        // Prepara i dati multipart come array di array
        $multipartFields = [
            [
                'name' => 'action',
                'contents' => 'create'
            ],
            [
                'name' => 'feature',
                'contents' => json_encode($feature, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'filename' => 'feature.json'
            ]
        ];

        // Aggiungi le immagini se presenti
        if (!empty($this->files)) {
            foreach ($this->files as $index => $fileInfo) {
                $filePath = storage_path('app/' . $fileInfo['temp_path']);
                if (file_exists($filePath)) {
                    $multipartFields[] = [
                        'name' => "images[{$index}]",
                        'contents' => fopen($filePath, 'r'),
                        'filename' => $fileInfo['original_name']
                    ];
                }
            }
        }

        return $multipartFields;
    }

    /**
     * Estrae il feature dai dati della request
     */
    private function extractFeatureFromRequestData(): array
    {
        $data = $this->requestData;

        // Se c'è un campo 'feature' che è una stringa JSON, decodificalo
        if (isset($data['feature']) && is_string($data['feature'])) {
            $feature = json_decode($data['feature'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($feature)) {
                return $feature;
            }
        }

        // Se c'è un campo 'feature' che è già un array
        if (isset($data['feature']) && is_array($data['feature'])) {
            return $data['feature'];
        }

        // Se non c'è un campo 'feature', costruiscilo dai dati disponibili
        $feature = [
            'type' => 'Feature',
            'properties' => [],
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [0, 0]
            ]
        ];

        // Copia le proprietà dai dati della richiesta
        foreach ($data as $key => $value) {
            if ($key !== 'feature' && $key !== 'files' && $key !== '_token' && $key !== 'images') {
                if (is_string($value) || is_numeric($value) || is_bool($value)) {
                    $feature['properties'][$key] = $value;
                } else {
                    $feature['properties'][$key] = json_encode($value);
                }
            }
        }

        // Gestisci la geometria se presente
        if (isset($data['geometry'])) {
            if (is_string($data['geometry'])) {
                $geometry = json_decode($data['geometry'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $feature['geometry'] = $geometry;
                }
            } elseif (is_array($data['geometry'])) {
                $feature['geometry'] = $data['geometry'];
            }
        }

        return $feature;
    }

    /**
     * Mappa gli app ID da geohub a osm2cai
     */
    private function mapAppId(int $originalAppId): int
    {
        $mapping = [
            26 => 1, // it.webmapp.osm2cai -> osm2cai
            20 => 2, // it.webmapp.sicai -> sicai
            58 => 3, // it.webmapp.acquasorgente -> acquasorgente
        ];

        return $mapping[$originalAppId] ?? $originalAppId;
    }



    /**
     * Pulisce i file temporanei
     */
    private function cleanupTempFiles(): void
    {
        foreach ($this->files as $fileInfo) {
            try {
                if (Storage::disk('local')->exists($fileInfo['temp_path'])) {
                    Storage::disk('local')->delete($fileInfo['temp_path']);
                    Log::info("🗑️ SendOsm2caiWebhookJob: File temporaneo eliminato", [
                        'file' => $fileInfo['temp_path']
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ SendOsm2caiWebhookJob: Errore nell'eliminazione file temporaneo", [
                    'file' => $fileInfo['temp_path'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
