<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'cai_parma_osm_poi_importer' => [
            'driver' => 'single',
            'path' => storage_path('logs/cai_parma_osm_poi_importer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'euma_importer' => [
            'driver' => 'single',
            'days' => '1',
            'level' => 'debug',
            'path' => storage_path('logs/euma_importer.log'),
            'bubble' => false,
        ],

        'layer' => [
            'driver' => 'single',
            'path' => storage_path('logs/layer.log'),
            'level' => 'debug',
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'osm2cai_importer' => [
            'driver' => 'single',
            'days' => '1',
            'level' => 'debug',
            'path' => storage_path('logs/osm2cai_importer.log'),
            'bubble' => false,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'pbf' => [
            'driver' => 'single',
            'path' => storage_path('logs/pbf.log'),
            'level' => 'debug',
            'days' => '1',
        ],

        'sentierisardegna_importer' => [
            'driver' => 'single',
            'path' => storage_path('logs/sentierisardegna_importer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
        ],

        'sicai_importer' => [
            'driver' => 'single',
            'path' => storage_path('logs/sicai_importer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'sisteco_importer' => [
            'driver' => 'single',
            'path' => storage_path('logs/sisteco_importer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'stdout' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stdout',
            ],
        ],

        'storagecsv_importer' => [
            'driver' => 'single',
            'path' => storage_path('logs/storagecsv_importer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'ugc' => [
            'driver' => 'single',
            'path' => storage_path('logs/ugc.log'),
            'level' => 'debug',
        ],

        'wp_importer' => [
            'driver' => 'single',
            'path' => storage_path('logs/wp_importer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
        ],

    ],

];
