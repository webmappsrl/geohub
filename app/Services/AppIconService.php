<?php

namespace App\Services;

use App\Models\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AppIconService
{
    private const CACHE_KEY = 'webmapp_app_icons';

    private const CACHE_LOOKUP_KEY = 'webmapp_app_icons_lookup';

    private const CACHE_TTL = 3600; // 1 ora

    /**
     * Ottiene tutte le icone delle app
     */
    public function getAllIcons(): array
    {
        // Prova prima la cache
        $icons = Cache::get(self::CACHE_KEY);

        if ($icons === null) {
            Log::info('AppIconService: caricamento icone dal database');
            $icons = $this->loadIconsFromDatabase();

            // Salva in cache
            Cache::put(self::CACHE_KEY, $icons, self::CACHE_TTL);
        } else {
            // Log::info('AppIconService: icone caricate dalla cache');
        }

        return $icons;
    }

    /**
     * Ottiene un'icona per identificatore SVG
     */
    public function getIconByIdentifier(string $svg): ?string
    {
        if (is_null($svg)) {
            return null;
        }

        $lookup = $this->getIconsLookup();

        return $lookup[$svg] ?? null;
    }

    /**
     * Ottiene il dizionario di lookup per accesso diretto alle icone
     */
    private function getIconsLookup(): array
    {
        // Prova prima la cache
        $lookup = Cache::get(self::CACHE_LOOKUP_KEY);

        if ($lookup === null) {
            // Log::info('AppIconService: creazione dizionario lookup dal database');
            $icons = $this->getAllIcons();

            $lookup = [];
            foreach ($icons as $icon) {
                $lookup[$icon['value']] = $icon['label'];
            }

            // Salva in cache
            Cache::put(self::CACHE_LOOKUP_KEY, $lookup, self::CACHE_TTL);
        }

        return $lookup;
    }

    /**
     * Ottiene le icone in formato array per Nova
     */
    public function getIconsForNova(): array
    {
        $icons = $this->getAllIcons();

        $iconsArray = [];
        foreach ($icons as $icon) {
            $iconsArray[$icon['label']] = $icon['value'];
        }

        return $iconsArray;
    }

    /**
     * Carica le icone dal database
     */
    private function loadIconsFromDatabase(): array
    {
        $appIcons = App::all()->filter(function ($i, $k) {
            return $i['iconmoon_selection'] != null;
        })->map(function ($item, $key) {
            $appInstance = str_replace('it.webmapp.', '', $item['app_id']);
            $currentJson = json_decode($item['iconmoon_selection'], true);
            $height = ($item['height']) ? $item['height'] : 1024;
            $prevSize = ($item['prevSize']) ? $item['prevSize'] : 32;

            if ($currentJson && $currentJson['icons'] && $currentJson['icons']) {
                return collect($currentJson['icons'])->map(function ($item, $key) use ($height, $prevSize) {
                    $height2 = $height / 2;

                    $icon = $item['icon'];
                    $svg = '';
                    if (! is_null($icon['paths'])) {
                        $svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$height} {$height}' width='{$prevSize}' height='{$prevSize}'><circle fill=\"darkorange\"  cx='{$height2}' cy='{$height2}' r='{$height2}'/><g fill=\"white\" transform='scale(0.8 0.8) translate(100, 100)'>";
                        foreach ($icon['paths'] as $path) {
                            $svg .= "<path d='{$path}'/>";
                        }
                        $svg .= '</g></svg>';
                    }
                    $identifier = 'no-name';
                    if (isset($icon['properties']) && isset($icon['properties']['name'])) {
                        $identifier = $icon['properties']['name'];
                    }
                    if (isset($icon['tags']) && isset($icon['tags'][0])) {
                        $identifier = $icon['tags'][0];
                    }

                    return [
                        'label' => $identifier,
                        'value' => $svg,
                        'search' => [$identifier],
                    ];
                });
            }

            return null;
        })->toArray();

        return array_merge(...$appIcons);
    }

    /**
     * Pulisce la cache delle icone
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_LOOKUP_KEY);
        Log::info('AppIconService: cache delle icone pulita');
    }

    /**
     * Forza il ricaricamento delle icone (pulisce cache e ricarica)
     */
    public function refreshIcons(): array
    {
        $this->clearCache();

        return $this->getAllIcons();
    }
}
