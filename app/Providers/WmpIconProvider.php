<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Bernhardh\NovaIconSelect\IconProvider;

class WmpIconProvider extends IconProvider
{
    public function __construct()
    {
        $json = file_get_contents('css/icons/webmapp-icons/selection.json');
        $content = json_decode($json, true);
        $options = array();
        foreach ($content['icons'] as $icon) {
            $options[] = [
                'label' => str_replace('-', ' ', $icon['properties']['name']),
                'value' => 'webmapp-icon-' . $icon['properties']['name'],
                'search' => explode('-', $icon['properties']['name'])
            ];
        }
        $this->setOptions($options);
    }
}
