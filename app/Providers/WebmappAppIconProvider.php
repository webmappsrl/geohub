<?php

namespace App\Providers;

use App\Models\App;
use Bernhardh\NovaIconSelect\IconProvider;

class WebmappAppIconProvider extends IconProvider
{
    private $icons;

    public function __construct()
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
        $this->icons = array_merge(...$appIcons);

        $this->setOptions($this->icons);
    }

    public function getIdentifier($svg)
    {
        $identifier = null;
        foreach ($this->icons as $item) {
            if ($item['value'] == $svg) {
                $identifier = $item['label'];
                break;
            }
        }

        return $identifier;
    }

    public function getIcons()
    {
        if (is_null($this->icons)) {
            $this->__construct();
        }

        $iconsArray = [];
        foreach ($this->icons as $icon) {
            $iconsArray[$icon['label']] = $icon['value'];
        }

        return $iconsArray;
    }
}
