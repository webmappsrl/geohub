<?php

return [
    'use_local_storage' => env('SAVE_MEDIA_ON_LOCAL_STORAGE', false),
    'distance_ec_poi' => env('EC_POI_MEDIA_DISTANCE', 500),
    'distance_ec_track' => env('EC_TRACK_MEDIA_DISTANCE', 500),
    'ectrack_share_page_feature_image_placeholder' => env('ECTRACK_SHARE_PAGE_FEATURE_IMAGE_PLACEHOLDER', storage_path('app/public/images').'/ectrack_share_page_feature_image_placeholder.jpg'),
];
