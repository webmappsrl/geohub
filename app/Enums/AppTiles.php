<?php

namespace App\Enums;

use ReflectionClass;

/**
 *  $t = new App\Enums\AppTiles()
 *  $t->getConstants()
 *  collect($t->getConstants())->pluck('url','name')
 */
class AppTiles
{
    const Notiles = [
        'name' => 'notile',
        'title' => [
            'it' => 'No Tiles'
        ],
        'icon' => 'path/to/icon.png',
        'url' => ''
    ];
    const Webmapp = [
        'oldval' => "{\"webmapp\":\"https://api.webmapp.it/tiles/{z}/{x}/{y}.png\"}",
        'name' => 'webmapp',
        'title' => [
            'it' => 'Webmapp'
        ],
        'icon' => 'path/to/icon.png',
        'url' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png'
    ];
    const Satellite = [
        'oldval' => [''],
        'name' => 'satellite',
        'title' => [
            'it' => 'Satellite'
        ],
        'icon' => 'path/to/icon.png',
        'url' => 'https://google.com'
    ];
    
    public function getConstants()
    {
        $reflectionClass = new ReflectionClass($this);
        return $reflectionClass->getConstants();
    }

    public function all() {
        $result = [];
        $all = collect($this->getConstants());
        $all->eachSpread(function ( $item, $key) {
            $result[] = $item;
        });
        return $result;
    }
}
