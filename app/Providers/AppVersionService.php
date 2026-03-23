<?php

namespace App\Providers;

use App\Models\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppVersionService
{
    const CACHE_TTL = 1800; // 30 minuti — bilancia freschezza del dato e protezione dal picco di richieste simultanoe al momento del rilascio

    /**
     * @param  bool  $fresh  Se true bypassa la cache e aggiorna il valore cachato (usare nel pannello Nova)
     */
    public function getVersionInfo(App $app, bool $fresh = false): array
    {
        $key = "app_version_{$app->id}";

        if ($fresh) {
            return tap([
                'ios' => $this->fetchIosVersion($app),
                'android' => $this->fetchAndroidVersion($app),
            ], fn ($data) => Cache::put($key, $data, self::CACHE_TTL));
        }

        return Cache::remember($key, self::CACHE_TTL, fn () => [
            'ios' => $this->fetchIosVersion($app),
            'android' => $this->fetchAndroidVersion($app),
        ]);
    }

    private function fetchIosVersion(App $app): ?array
    {
        if (empty($app->sku)) {
            return null;
        }

        try {
            $results = Http::timeout(10)
                ->get('https://itunes.apple.com/lookup', [
                    'bundleId' => $app->sku,
                    'country' => 'it',
                ])
                ->json('results', []);

            // Retry senza country se non trovato nella store italiana
            if (empty($results)) {
                $results = Http::timeout(10)
                    ->get('https://itunes.apple.com/lookup', ['bundleId' => $app->sku])
                    ->json('results', []);
            }

            if (! empty($results[0])) {
                return [
                    'version' => $results[0]['version'] ?? null,
                    'release_notes' => $results[0]['releaseNotes'] ?? null,
                    'store_url' => $results[0]['trackViewUrl'] ?? $app->ios_store_link,
                ];
            }
        } catch (\Exception $e) {
            Log::warning("AppVersionService: iOS lookup failed for app {$app->id}: ".$e->getMessage());
        }

        return null;
    }

    private function fetchAndroidVersion(App $app): ?array
    {
        $packageId = $this->extractAndroidPackageId($app);
        if (! $packageId) {
            return null;
        }

        try {
            $html = Http::timeout(15)
                ->withHeaders([
                    'Accept-Language' => 'it-IT,it;q=0.9',
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])
                ->get('https://play.google.com/store/apps/details', [
                    'id' => $packageId,
                    'hl' => 'it',
                    'gl' => 'IT',
                ])
                ->body();

            return [
                'version' => $this->parseAndroidVersion($html),
                'release_notes' => $this->parseAndroidReleaseNotes($html),
                'store_url' => $app->android_store_link,
            ];
        } catch (\Exception $e) {
            Log::warning("AppVersionService: Android scrape failed for app {$app->id}: ".$e->getMessage());
        }

        return null;
    }

    private function extractAndroidPackageId(App $app): ?string
    {
        if (! empty($app->android_store_link)) {
            parse_str(parse_url($app->android_store_link, PHP_URL_QUERY) ?? '', $params);
            if (! empty($params['id'])) {
                return $params['id'];
            }
        }

        return $app->sku ?? null;
    }

    private function parseAndroidVersion(string $html): ?string
    {
        // Pattern principale: il Play Store incorpora la versione corrente come [[["X.Y.Z"]]
        // nel payload JSON embedded della pagina (confermato empiricamente su Chrome 120+)
        if (preg_match('/\[\[\["(\d+\.\d+[\d\.]*)"\]\]/', $html, $m)) {
            return $m[1];
        }

        // Fallback: meta tag itemprop (legacy, non più usato dal Play Store ma per sicurezza)
        if (preg_match('/<meta itemprop="softwareVersion" content="([^"]+)"/', $html, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function parseAndroidReleaseNotes(string $html): ?string
    {
        // Il Play Store mostra le release notes nella sezione "What's new"
        // L'apostrofo è unicode \u2019 ('), non ASCII '
        // In italiano il Play Store mostra "Novità", in inglese "What's new" (apostrofo unicode \u2019)
        $whatsNewPos = strpos($html, 'Novit');
        if ($whatsNewPos === false) {
            $whatsNewPos = strpos($html, "What\u{2019}s new");
        }
        if ($whatsNewPos !== false) {
            $chunk = substr($html, $whatsNewPos, 2000);
            if (preg_match('/itemprop="description">(.*?)<\/div>/s', $chunk, $m)) {
                $raw = $m[1];
                $text = preg_replace('/<br\s*\/?>/i', "\n", $raw);
                $text = strip_tags($text);

                return html_entity_decode(trim($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return null;
    }
}
