<?php

return [
    'osf_media_storage_name' => env('OUT_SOURCE_FEATURE_STORAGE_NAME', 'importer-osfmedia'),
    'use_local_storage' => env('SAVE_MEDIA_ON_LOCAL_STORAGE', false),
    'ec_poi_media_distance' => env('EC_POI_MEDIA_DISTANCE', 500),
    'ec_track_media_distance' => env('EC_TRACK_MEDIA_DISTANCE', 500),
    'ec_track_ec_poi_distance' => env('EC_TRACK_POI_DISTANCE', 500),
    'ectrack_share_page_feature_image_placeholder' => env('ECTRACK_SHARE_PAGE_FEATURE_IMAGE_PLACEHOLDER', storage_path('app/public/images') . '/ectrack_share_page_feature_image_placeholder.jpg'),
];
