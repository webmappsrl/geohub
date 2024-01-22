<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'elastic' => [
        'host' => env('ELASTIC_HOST'),
        'key' => env('ELASTIC_KEY'),
        'http' => env('ELASTIC_HTTP'),
        'password' => env('ELASTIC_PASSWORD'),
        'username' => env('ELASTIC_USERNAME'),
    ],

    'importers' => [
        'ecTracks' => [
            'validHeaders' => ['id', 'from', 'to', 'ele_from', 'ele_to', 'distance', 'duration_forward', 'duration_backward', 'ascent', 'descent', 'ele_min', 'ele_max', 'difficulty'],
        ],
        'ecPois' =>
        [
            'validHeaders' => ['id', 'name_it', 'name_en', 'description_it', 'description_en', 'poi_type', 'theme', 'lat', 'lng', 'addr_complete', 'capacity', 'contact_phone', 'contact_email', 'related_url','feature_image']
        ]
    ],

    'dem' => [
        'host' => env('DEM_HOST', 'https://dem.maphub.it'),
        'ele_api' => env('DEM_ELE_API', '/api/v1/elevation'),
        'tech_data_api' => env('DEM_TECH_DATA_API', '/api/v1/track'),
    ],
    
    'emails' => [
        'sardegna_sentieri' => env('EMAILS_SARDEGNA_SENTIERI', 'pedramkatanchi@webmapp.it'),
        'cai_parma' => env('EMAILS_CAI_PARMA', 'pedramkatanchi@webmapp.it'),
    ],
];
