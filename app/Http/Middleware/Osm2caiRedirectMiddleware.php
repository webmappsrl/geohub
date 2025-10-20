<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendOsm2caiWebhookJob;

class Osm2caiRedirectMiddleware
{
    /**
     * App ID che devono essere ridirezionati verso osm2cai
     */
    private const REDIRECT_APP_IDS = [20, 26, 58];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Monitora solo le richieste UGC store
        if ($this->isUgcStoreRequest($request)) {
            $appId = $this->getAppId($request);

            if (in_array($appId, self::REDIRECT_APP_IDS)) {
                Log::info("🚫 Osm2caiRedirectMiddleware: UGC store rilevato", [
                    'app_id' => $appId,
                    'path' => $request->path(),
                    'method' => $request->method()
                ]);

                return $this->returnAppMigrationError();
            }
        }

        return $next($request);
    }

    /**
     * Controlla se è una richiesta UGC store
     */
    private function isUgcStoreRequest(Request $request): bool
    {
        return $request->isMethod('POST') && preg_match('#ugc/.+/store#', $request->path());
    }

    /**
     * Estrae l'app_id dalla richiesta
     */
    private function getAppId(Request $request): ?int
    {
        // 1. Header
        $appId = $request->header('app-id');
        if ($appId && is_numeric($appId)) {
            return (int) $appId;
        }
        $appId = $request->header('App-id');
        if ($appId && is_numeric($appId)) {
            return (int) $appId;
        }

        // 2. Properties nel body (solo POST)
        if ($request->isMethod('POST')) {
            $data = $request->all();
            if (isset($data['properties']['appId']) && is_numeric($data['properties']['appId'])) {
                return (int) $data['properties']['appId'];
            }
            // 3. appId diretto nel body
            if (isset($data['appId']) && is_numeric($data['appId'])) {
                return (int) $data['appId'];
            }
        }
        return null;
    }

    /**
     * Invia webhook a osm2cai tramite job
     */
    private function sendWebhookToOsm2cai(Request $request, int $appId)
    {
        // Determina il tipo di UGC (poi o track) dal path
        $ugcType = $this->getUgcTypeFromPath($request->path());

        // Estrai l'UUID dalla request originale
        $feature = $this->extractFeatureFromRequest($request);
        $uuid = $feature['properties']['uuid'] ?? null;

        Log::info("🔍 Osm2caiRedirectMiddleware: UUID estratto dalla request", [
            'uuid' => $uuid,
            'app_id' => $appId,
            'ugc_type' => $ugcType
        ]);

        // Prepara i dati della request per il job (senza UploadedFile)
        $requestData = $request->all();

        // Salva gli headers necessari per l'autenticazione
        $headers = [
            'Authorization' => $request->header('Authorization'),
            'Accept' => $request->header('Accept'),
            'Content-Type' => $request->header('Content-Type'),
            'User-Agent' => $request->header('User-Agent'),
            'app-id' => $request->header('app-id'),
            'App-id' => $request->header('App-id')
        ];

        Log::info("📋 Osm2caiRedirectMiddleware: Headers salvati per il job", [
            'has_authorization' => !empty($headers['Authorization']),
            'authorization_length' => strlen($headers['Authorization'] ?? ''),
            'headers_count' => count(array_filter($headers))
        ]);

        // Estrai l'email dell'utente
        $userEmail = $this->getUserEmail($request);
        if ($userEmail) {
            Log::info("📧 Osm2caiRedirectMiddleware: Email utente estratta", [
                'email' => $userEmail
            ]);
        } else {
            Log::warning("⚠️ Osm2caiRedirectMiddleware: Email utente non trovata");
        }

        // Gestisci i file separatamente
        $files = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                if ($file->isValid()) {
                    // Salva il file temporaneamente
                    $tempPath = $file->store('temp/webhook', 'local');
                    $files[] = [
                        'temp_path' => $tempPath,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize()
                    ];
                }
            }
        }

        // Rimuovi i file dalla request data per evitare problemi di serializzazione
        unset($requestData['images']);

        Log::info("🚀 Osm2caiRedirectMiddleware: Dispatch job per webhook", [
            'app_id' => $appId,
            'ugc_type' => $ugcType,
            'uuid' => $uuid
        ]);

        // Dispatch del job con delay di 30 secondi sulla coda dedicata
        SendOsm2caiWebhookJob::dispatch($requestData, $appId, $uuid, $ugcType, $files, $headers, $userEmail)
            ->onQueue('osm2cai-webhooks')
            ->delay(now()->addSeconds(30));
    }

    /**
     * Controlla se la richiesta contiene immagini
     */
    private function hasImages(Request $request): bool
    {
        $data = $request->all();
        return isset($data['images']) && is_array($data['images']) && !empty($data['images']);
    }



    /**
     * Invia webhook multipart (con immagini)
     */
    private function sendMultipartWebhook(Request $request, int $appId, string $webhookUrl, string $ugcType)
    {
        // Prepara i dati multipart
        $multipartFields = $this->prepareMultipartWebhookData($request, $appId);

        Log::info("📤 Osm2caiRedirectMiddleware: Invio webhook multipart", [
            'webhook_url' => $webhookUrl,
            'ugc_type' => $ugcType,
            'action' => 'create',
            'fields_count' => count($multipartFields),
            'has_images' => collect($multipartFields)->contains('name', 'images[0]')
        ]);

        try {
            // Prepara headers per il webhook multipart
            $webhookHeaders = [
                'Accept' => 'application/json',
                'User-Agent' => 'Geohub-Webhook/1.0',
                'X-Geohub-Redirect' => 'true',
                'X-Geohub-Source' => 'geohub',
                'X-Geohub-Original-App-Id' => $appId
            ];

            // Aggiungi l'email dell'utente se disponibile
            $userEmail = $this->getUserEmail($request);
            if ($userEmail) {
                $webhookHeaders['X-Geohub-User-Email'] = $userEmail;
            }

            // Invia la richiesta multipart
            $response = Http::timeout(30)
                ->withHeaders($webhookHeaders)
                ->asMultipart()
                ->post($webhookUrl, $multipartFields);

            Log::info("📥 Osm2caiRedirectMiddleware: Risposta webhook multipart", [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'success' => $response->successful()
            ]);
        } catch (\Exception $e) {
            Log::error("❌ Osm2caiRedirectMiddleware: Errore webhook multipart", [
                'error' => $e->getMessage(),
                'app_id' => $appId
            ]);
        }
    }

    /**
     * Prepara i dati per il webhook multipart
     */
    private function prepareMultipartWebhookData(Request $request, int $appId): array
    {
        $data = $request->all();
        $multipartData = [];

        // Prepara il feature
        $feature = $this->extractFeatureFromRequest($request);

        // Mappa l'app_id da geohub a osm2cai
        $mappedAppId = $this->mapAppId($appId);
        if (!isset($feature['properties']['app_id'])) {
            $feature['properties']['app_id'] = $mappedAppId;
        } else {
            $feature['properties']['app_id'] = $mappedAppId;
        }

        // Cerca l'UGC tramite UUID e aggiungi il geohub_id
        if (isset($feature['properties']['uuid'])) {
            $uuid = $feature['properties']['uuid'];
            $geohubId = $this->findUgcByUuid($uuid, $appId);

            if ($geohubId) {
                $feature['properties']['geohub_id'] = $geohubId;
                Log::info("✅ Osm2caiRedirectMiddleware: geohub_id aggiunto al feature", [
                    'uuid' => $uuid,
                    'geohub_id' => $geohubId,
                    'app_id' => $appId
                ]);
            } else {
                Log::warning("⚠️ Osm2caiRedirectMiddleware: UGC non trovato per UUID", [
                    'uuid' => $uuid,
                    'app_id' => $appId
                ]);
            }
        } else {
            Log::warning("⚠️ Osm2caiRedirectMiddleware: UUID non presente nel feature", [
                'app_id' => $appId,
                'available_properties' => array_keys($feature['properties'] ?? [])
            ]);
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
        if (isset($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $index => $file) {
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $multipartFields[] = [
                        'name' => "images[{$index}]",
                        'contents' => fopen($file->getRealPath(), 'r'),
                        'filename' => $file->getClientOriginalName()
                    ];
                }
            }
        }

        return $multipartFields;
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
     * Determina il tipo di UGC dal path della richiesta
     */
    private function getUgcTypeFromPath(string $path): string
    {
        if (str_contains($path, '/poi/')) {
            return 'poi';
        } elseif (str_contains($path, '/track/')) {
            return 'track';
        }

        // Default fallback
        return 'poi';
    }

    /**
     * Prepara i dati per il webhook di osm2cai2
     */
    private function prepareWebhookData(Request $request, int $appId): array
    {
        // Estrai il feature dai dati
        $feature = $this->extractFeatureFromRequest($request);

        // Assicurati che l'app_id sia presente nel feature (osm2cai si aspetta app_id)
        if (!isset($feature['properties']['app_id'])) {
            $feature['properties']['app_id'] = $appId;
        }

        return [
            'action' => 'create',
            'feature' => $feature
        ];
    }

    /**
     * Estrae il feature dalla richiesta
     */
    private function extractFeatureFromRequest(Request $request): array
    {
        $data = $request->all();

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
            if ($key !== 'feature' && $key !== 'files' && $key !== '_token') {
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
     * Cerca l'UGC tramite UUID e restituisce l'ID di Geohub
     */
    private function findUgcByUuid(string $uuid, int $appId): ?int
    {
        try {
            // Determina il tipo di UGC dal path della richiesta corrente
            $ugcType = $this->getUgcTypeFromPath(request()->path());

            // Cerca nei modelli appropriati
            if ($ugcType === 'poi') {
                $ugc = \App\Models\UgcPoi::where('app_id', $appId)
                    ->whereJsonContains('properties->uuid', $uuid)
                    ->first();
            } elseif ($ugcType === 'track') {
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

            if ($ugc) {
                Log::info("✅ Osm2caiRedirectMiddleware: UGC trovato tramite UUID", [
                    'uuid' => $uuid,
                    'ugc_id' => $ugc->id,
                    'ugc_type' => $ugcType,
                    'app_id' => $appId
                ]);
                return $ugc->id;
            }

            Log::warning("❌ Osm2caiRedirectMiddleware: UGC non trovato tramite UUID", [
                'uuid' => $uuid,
                'ugc_type' => $ugcType,
                'app_id' => $appId
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("❌ Osm2caiRedirectMiddleware: Errore nella ricerca UGC tramite UUID", [
                'uuid' => $uuid,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Estrae l'email dell'utente dal token JWT o dall'utente autenticato
     */
    private function getUserEmail(Request $request): ?string
    {
        try {
            // Prova a ottenere l'utente autenticato
            $user = auth('api')->user();
            if ($user && $user->email) {
                return $user->email;
            }

            // Se non c'è un utente autenticato, prova a decodificare il token JWT
            $token = $request->header('Authorization');
            if ($token && strpos($token, 'Bearer ') === 0) {
                $token = substr($token, 7); // Rimuovi "Bearer "

                // Decodifica il token JWT per ottenere l'ID utente
                $payload = \Tymon\JWTAuth\Facades\JWTAuth::manager()->decode(
                    \Tymon\JWTAuth\Facades\JWTAuth::token($token),
                    \Tymon\JWTAuth\Facades\JWTAuth::manager()->getJWTProvider()
                );

                if (isset($payload['sub'])) {
                    $user = \App\Models\User::find($payload['sub']);
                    if ($user && $user->email) {
                        return $user->email;
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("⚠️ Osm2caiRedirectMiddleware: Errore nell'estrazione email utente", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Restituisce errore per app in migrazione
     */
    private function returnAppMigrationError()
    {
        return response(['error' => 'App needs to be updated to continue syncing.'], 403);
    }
}
