<?php

namespace App\Nova\Fields;


use App\Models\App;
use Laravel\Nova\Fields\Text;
use App\Providers\AppVersionService;

class StoreVersionField extends Text
{
    public function __construct()
    {
        parent::__construct(__('Store Versions'), 'store_versions');

        $this
            ->asHtml()
            ->onlyOnDetail()
            ->hideFromIndex()
            ->resolveUsing(function ($value, App $app) {
                $data = (new AppVersionService)->getVersionInfo($app, true);

                return $this->renderHtml($data);
            });
    }

    public static function make(...$args)
    {
        return new static;
    }

    private function renderHtml(array $data): string
    {
        $ios = $this->renderPlatform($data['ios'] ?? null, 'App Store (iOS)', '🍎');
        $android = $this->renderPlatform($data['android'] ?? null, 'Play Store (Android)', '🤖');

        return <<<HTML
        <div style="margin-top:1rem">
            <h3 style="font-weight:700;margin-bottom:1rem;font-size:1.1rem">Versioni pubblicate sugli store</h3>
            {$ios}
            {$android}
        </div>
        HTML;
    }

    private function renderPlatform(?array $info, string $label, string $icon): string
    {
        if (! $info) {
            return "<div style='margin-bottom:1rem;padding:1rem;border:1px solid #e5e7eb;border-radius:8px'>
                        <strong>{$icon} {$label}</strong>: <em style='color:#9ca3af'>dati non disponibili</em>
                    </div>";
        }

        $version = htmlspecialchars($info['version'] ?? '—');
        $notes = nl2br(htmlspecialchars($info['release_notes'] ?? '—'));
        $url = htmlspecialchars($info['store_url'] ?? '');
        $link = $url ? "<a href='{$url}' target='_blank' style='margin-left:8px;font-size:0.85em;color:#3b82f6'>Apri store ↗</a>" : '';

        return <<<HTML
        <div style="margin-bottom:1rem;padding:1rem;border:1px solid #e5e7eb;border-radius:8px">
            <div style="font-weight:600;margin-bottom:0.5rem">{$icon} {$label} {$link}</div>
            <div style="margin-bottom:0.4rem"><strong>Versione:</strong> <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px">{$version}</code></div>
            <div><strong>Note di rilascio:</strong><br><span style="font-size:0.9em;color:#4b5563;line-height:1.6">{$notes}</span></div>
        </div>
        HTML;
    }
}
