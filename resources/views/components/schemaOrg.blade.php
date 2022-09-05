@props(['track','startPoint', 'appSocialText'])
@php
    use Spatie\SchemaOrg\Schema;
    $CreativeWorkSeries = Schema::CreativeWorkSeries()
        ->headline($track->name)
        ->name($track->name)
        ->mainEntityOfPage(url()->current())
        ->publisher(Schema::Organization()
            ->name('Webmapp')
            ->url('https://webmapp.it')
                ->logo(Schema::ImageObject()
                    ->url('https://webmapp.it/wp-content/uploads/2016/07/webamapp-logo-1.png')
                )
        )
        ->dateCreated($track->created_at)
        ->datePublished($track->updated_at)
        ->url(url()->current())
        ->description($appSocialText)
        ->image($track->featureImage?$track->featureImage->thumbnail('1440x500'):asset('images/ectrack_share_page_feature_image_placeholder.jpg'))
        ->mainEntity(Schema::TouristAttraction()
            ->name($track->name)
            ->geo(Schema::GeoCoordinates()
                ->latitude($startPoint[0])
                ->longitude($startPoint[1])
            )
            ->isAccessibleForFree(true)
            ->publicAccess(!$track->not_accessible)
        )
        ;

    echo $CreativeWorkSeries;
@endphp