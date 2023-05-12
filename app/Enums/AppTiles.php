<?php

namespace App\Enums;

use Illuminate\Support\Facades\Storage;
use ReflectionClass;

/**
 *  $t = new App\Enums\AppTiles()
 *  $t->getConstants()
 *  collect($t->getConstants())->pluck('url','name')
 */
class AppTiles
{
    
    const notile = [
        'name' => 'notile',
        'label' => [
            'it' => 'No Tiles'
        ],
        'icon' => 'https://geohub.webmapp.it/storage/layers/notile.png',
        'url' => ""
    ];
    const webmapp = [
        'name' => 'webmapp',
        'label' => [
            'it' => 'Webmapp'
        ],
        'icon' => 'https://geohub.webmapp.it/storage/layers/webmapp.png',
        'url' => "https://api.webmapp.it/tiles/{z}/{x}/{y}.png"
    ];
    const mute = [
        'name' => 'mute',
        'label' => [
            'it' => 'Mute'
        ],
        'icon' => 'https://geohub.webmapp.it/storage/layers/mute.png',
        'url' => "http://tiles.webmapp.it/blankmap/{z}/{x}/{y}.png"
    ];
    const satellite = [
        'name' => 'satellite',
        'label' => [
            'it' => 'Satellite'
        ],
        'icon' => 'https://geohub.webmapp.it/storage/layers/satellite.png',
        'url' => "https://api.maptiler.com/tiles/satellite/{z}/{x}/{y}.jpg?key=0Z7ou7nfFFXipdDXHChf"
    ];
    const GOMBITELLI = [
        'name' => 'GOMBITELLI',
        'label' => [
            'it' => 'Gombitelli'
        ],
        'icon' => 'https://geohub.webmapp.it/storage/layers/gombitelli.png',
        'url' => "https://tiles.webmapp.it/mappa_gombitelli/{z}/{x}/{y}.png"
    ];
    
    public function getConstants()
    {
        $reflectionClass = new ReflectionClass($this);
        return $reflectionClass->getConstants();
    }

    public function getConstant($const = null)
    {
        $reflectionClass = new ReflectionClass($this);
        return $reflectionClass->getConstant($const);
    }

    public function oldval() {
        $all = collect($this->getConstants());
        $result = [];
        foreach ($all as $key => $val) {
            $first = [$val['name'] => $val['url']];
            $result[json_encode($first,JSON_UNESCAPED_SLASHES)] = $val['label']['it'];
        }
        return $result;
    }
}
