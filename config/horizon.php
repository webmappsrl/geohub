<?php

use Illuminate\Support\Str;

return [

    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),

    'use' => 'default',

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_') . '_horizon:'
    ),

    'middleware' => ['web'],

    'waits' => [
        'redis:default' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [
        // App\Jobs\ExampleJob::class,
    ],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => 256,

    'defaults' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'maxTime' => 3600,
            'maxJobs' => 1000,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 120,
            'nice' => 0,
        ],
        'supervisor-pbf' => [
            'connection' => 'redis',
            'queue' => ['pbf'],
            'balance' => 'auto', // Usa il bilanciamento automatico
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 15, // Massimo 10 processi disponibili
            'minProcesses' => 5, // Assicurati di avere almeno 1 processo sempre attivo
            'balanceMaxShift' => 10, // Massimo incremento o riduzione di processi
            'balanceCooldown' => 3, // Tempo di raffreddamento prima di cambiare processi
            'maxTime' => 3600,
            'maxJobs' => 1000,
            'memory' => 256,
            'tries' => 5,
            'timeout' => 180,
            'nice' => 0,
        ],
        'supervisor-layers' => [
            'connection' => 'redis',
            'queue' => ['layers'],
            'balance' => 'simple',
            'maxProcesses' => 5,
            'maxTime' => 3600,
            'maxJobs' => 500,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 120,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-default' => [
                'maxProcesses' => 20,
                'balanceMaxShift' => 3,
                'balanceCooldown' => 3,
            ],
            'supervisor-pbf' => [
                'maxProcesses' => 10,
                'balanceMaxShift' => 3,
                'balanceCooldown' => 3,
            ],
            'supervisor-layers' => [
                'maxProcesses' => 5,
            ],
        ],
        'local' => [
            'supervisor-default' => [
                'maxProcesses' => 20,
            ],
            'supervisor-pbf' => [
                'maxProcesses' => 20,
                'balanceMaxShift' => 3,
                'balanceCooldown' => 3,
            ],
            'supervisor-layers' => [
                'maxProcesses' => 1, // Limitato a 2 processi in ambiente locale
            ],
        ],
    ],
];
