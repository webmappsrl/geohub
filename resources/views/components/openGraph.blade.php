@php
    $og = OpenGraph::title($track->name)
    ->siteName('Geohub | Webmapp')
    ->image($track->featureImage?$track->featureImage->thumbnail('1440x500'):asset('images/ectrack_share_page_feature_image_placeholder.jpg'), [
        'width' => 1440,
        'height' => 500
    ])
    ->description($appSocialText);
@endphp
{!! $og->renderTags() !!}
