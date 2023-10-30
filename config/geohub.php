<?php

return [
    'sardegna_sentieri_api_username' => env('SARDEGNA_SENTIERI_API_USERNAME', ''),
    'sardegna_sentieri_api_password' => env('SARDEGNA_SENTIERI_API_PASSWORD', ''),
    'audio_media_storage_name' => env('AUDIO_MEDIA_STORAGE_NAME', 's3-osfmedia-test'),
    'osf_media_storage_name' => env('OUT_SOURCE_FEATURE_STORAGE_NAME', 'importer-osfmedia'),
    'ec_media_storage_name' => env('EC_MEDIA_STORAGE_NAME', 'public'),
    'use_local_storage' => env('SAVE_MEDIA_ON_LOCAL_STORAGE', false),
    'ec_poi_media_distance' => env('EC_POI_MEDIA_DISTANCE', 500),
    'ec_track_media_distance' => env('EC_TRACK_MEDIA_DISTANCE', 500),
    'ec_track_ec_poi_distance' => env('EC_TRACK_POI_DISTANCE', 500),
    'ectrack_share_page_feature_image_placeholder' => env('ECTRACK_SHARE_PAGE_FEATURE_IMAGE_PLACEHOLDER', storage_path('app/public/images') . '/ectrack_share_page_feature_image_placeholder.jpg'),
    'app_env' => env('APP_ENV') === 'production' ? 's3' : 'public',
    'elastic_low_geom_tollerance' => env('ELASTIC_LOW_GEOM_TOLLERANCE',0.006),
];
