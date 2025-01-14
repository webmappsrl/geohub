<?php

return [
    'audio_media_storage_name' => env('AUDIO_MEDIA_STORAGE_NAME', 's3-osfmedia-test'),
    'osf_media_storage_name' => env('OUT_SOURCE_FEATURE_STORAGE_NAME', 'importer-osfmedia'),
    's3_pbf_storage_name' => env('AWS_WMPBF_STORAGE_NAME', 's3-wmpbf'),
    'ec_media_storage_name' => env('EC_MEDIA_STORAGE_NAME', 'public'),
    'use_local_storage' => env('SAVE_MEDIA_ON_LOCAL_STORAGE', false),
    'ec_poi_media_distance' => env('EC_POI_MEDIA_DISTANCE', 500),
    'ec_track_media_distance' => env('EC_TRACK_MEDIA_DISTANCE', 500),
    'ec_track_ec_poi_distance' => env('EC_TRACK_POI_DISTANCE', 500),
    'ectrack_share_page_feature_image_placeholder' => env('ECTRACK_SHARE_PAGE_FEATURE_IMAGE_PLACEHOLDER', storage_path('app/public/images').'/ectrack_share_page_feature_image_placeholder.jpg'),
    'app_env' => env('APP_ENV') === 'production' ? 's3' : 'public',
    'elastic_low_geom_tollerance' => env('ELASTIC_LOW_GEOM_TOLLERANCE', 0.006),
    'pbf_min_zoom' => env('PBF_MIN_ZOOM', 5),
    'pbf_max_zoom' => env('PBF_MAX_ZOOM', 13),
    'node_executable' => env('NODE_EXECUTABLE', '/usr/bin/node'),
    'ec_media' => [
        'thumbnail_sizes' => [
            ['width' => 108, 'height' => 148],
            ['width' => 108, 'height' => 137],
            ['width' => 150, 'height' => 150],
            ['width' => 225, 'height' => 100],
            ['width' => 118, 'height' => 138],
            ['width' => 108, 'height' => 139],
            ['width' => 118, 'height' => 117],
            ['width' => 335, 'height' => 250],
            ['width' => 400, 'height' => 200],
            ['width' => 1440, 'height' => 500],
            ['width' => 1920, 'height' => 0],
            ['width' => 250, 'height' => 150],
        ],
    ],
];
