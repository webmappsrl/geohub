<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'importer' => [
            'driver' => 'local',
            'root' => storage_path('importer'),
        ],

        'mapping' => [
            'driver' => 'local',
            'root' => storage_path('importer/mapping'),
        ],

        'pois' => [
            'driver' => 'local',
            'root' => storage_path('json/pois'),
        ],

        'conf' => [
            'driver' => 'local',
            'root' => storage_path('json/conf'),
        ],

        'osm2cai' => [
            'driver' => 'local',
            'root' => storage_path('importer/osm2cai'),
        ],

        'importer-osfmedia' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'visibility' => 'public',
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false)
        ],
        'wmdumps' => [
            'driver' => 's3',
            'key' => env('AWS_DUMPS_ACCESS_KEY_ID'),
            'secret' => env('AWS_DUMPS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_DUMPS_BUCKET'),
            'url' => env('AWS_DUMPS_URL', env('AWS_URL')),
            'endpoint' => env('AWS_DUMPS_ENDPOINT', env('AWS_ENDPOINT')),
            'use_path_style_endpoint' => env('AWS_DUMPS_USE_PATH_STYLE_ENDPOINT', env('AWS_USE_PATH_STYLE_ENDPOINT', false))
        ],
        'wmfeconf' => [
            'driver' => 's3',
            'key' => env('AWS_DUMPS_ACCESS_KEY_ID'),
            'secret' => env('AWS_DUMPS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_FE_CONF_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'root' => 'geohub/conf',
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false)
        ],
        'wmfepois' => [
            'driver' => 's3',
            'key' => env('AWS_DUMPS_ACCESS_KEY_ID'),
            'secret' => env('AWS_DUMPS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_FE_CONF_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'root' => 'geohub/pois',
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false)
        ],
        'wmfetracks' => [
            'driver' => 's3',
            'key' => env('AWS_DUMPS_ACCESS_KEY_ID'),
            'secret' => env('AWS_DUMPS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_FE_CONF_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'root' => 'geohub/tracks',
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false)
        ],
        's3-osfmedia' => [
            'driver' => 's3',
            'key' => env('AWS_OSFMEDIA_ACCESS_KEY_ID'),
            'secret' => env('AWS_OSFMEDIA_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_OSFMEDIA_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false)
        ],
        's3-wmpbf' => [
            'driver' => 's3',
            'key' => env('AWS_OSFMEDIA_ACCESS_KEY_ID'),
            'secret' => env('AWS_OSFMEDIA_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_WMPBF_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false)
        ],
        's3-osfmedia-test' => [
            'driver' => 's3',
            'key' => env('AWS_OSFMEDIA_TEST_ACCESS_KEY_ID'),
            'secret' => env('AWS_OSFMEDIA_TEST_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_OSFMEDIA_TEST_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false)
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
