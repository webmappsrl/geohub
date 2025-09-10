<?php

namespace App\Providers;

use App\Services\AppIconService;
use Bernhardh\NovaIconSelect\IconProvider;
use Illuminate\Support\Facades\Log;

class WebmappAppIconProvider extends IconProvider
{
    private $icons;
    private ?AppIconService $iconService = null;

    public function __construct()
    {
        $this->icons = $this->getIconService()->getAllIcons();
        $this->setOptions($this->icons);
    }

    /**
     * Ottiene il servizio AppIconService tramite il service container
     */
    private function getIconService(): AppIconService
    {
        if ($this->iconService === null) {
            $this->iconService = app(AppIconService::class);
        }
        return $this->iconService;
    }

    public function getIdentifier($svg)
    {
        return $this->getIconService()->getIconByIdentifier($svg);
    }

    public function getIcons()
    {
        if (is_null($this->icons)) {
            $this->icons = $this->getIconService()->getAllIcons();
        }

        return $this->getIconService()->getIconsForNova();
    }

    /**
     * Pulisce la cache delle icone
     */
    public static function clearCache(): void
    {
        app(AppIconService::class)->clearCache();
    }
}
