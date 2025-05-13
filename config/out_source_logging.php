<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Importer Logging Channels
    |--------------------------------------------------------------------------
    |
    | This array maps importer provider keys (lowercase) to their specific
    | logging channels. This allows for flexible log management without
    | needing to modify command code.
    |
    */

    'importer_provider_channels' => [
        'wp' => 'wp_importer',
        'storagecsv' => 'storagecsv_importer',
        'osm2cai' => 'osm2cai_importer',
        'sicai' => 'sicai_importer',
        'euma' => 'euma_importer',
        'osmpoi' => 'cai_parma_osm_poi_importer',
        'sentierisardegna' => 'sentierisardegna_importer',
        'sisteco' => 'sisteco_importer',
    ],

    'sync_provider_channels' => [
        'outsourceimporterfeaturestoragecsv' => 'storagecsv_importer',
        'outsourceimporterfeatureosmpoi' => 'cai_parma_osm_poi_importer',
        'outsourceimporterfeaturesentierisardegna' => 'sentierisardegna_importer',
        'outsourceimporterfeaturesicai' => 'sicai_importer',
        'outsourceimporterfeatureosm2cai' => 'osm2cai_importer',
        'outsourceimporterfeatureeuma' => 'euma_importer',
        'outsourceimporterfeaturewp' => 'wp_importer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Importer Log Channel
    |--------------------------------------------------------------------------
    |
    | This channel will be used if a specific provider is not found in the
    | 'provider_channels' map, or if no provider is specified.
    |
    */
    'default_channel' => 'stack',
];
