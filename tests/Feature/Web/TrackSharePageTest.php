<?php

namespace Tests\Feature\Web;

use App\Models\EcTrack;
use App\Models\EcMedia;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyWhere;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TrackSharePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_with_not_existing_id_then_it_returns_404()
    {
        EcTrack::truncate();
        $track = EcTrack::factory()->create();
        $response = $this->get('/track/' . (intval($track->id) + 1));
        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_with_existing_id_then_it_returns_200()
    {
        $track = EcTrack::factory()->create();
        $response = $this->get('/track/' . $track->id);
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_then_the_title_is_correct()
    {
        $track = EcTrack::factory()->create();
        $response = $this->get('/track/' . $track->id);
        $response->assertSee($track->name);
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_with_feature_image_then_feature_image_is_correct()
    {
        $m = EcMedia::factory()->frontEndSizes()->create();
        $t = EcTrack::factory()->create([
            'feature_image' => $m->id
        ]);
        $r = $this->get('/track/' . $t->id);
        $r->assertSee($m->thumbnail('1440x500'));
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_without_feature_image_then_it_returns_200()
    {
        $t = EcTrack::factory()->create([
            'feature_image' => null
        ]);
        $r = $this->get('/track/' . $t->id);
        $r->assertStatus(200);
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_without_feature_image_then_feature_image_is_placeholder()
    {
        $t = EcTrack::factory()->create([
            'feature_image' => null
        ]);
        $r = $this->get('/track/' . $t->id);
        $title = explode("/", config("geohub.ectrack_share_page_feature_image_placeholder"));
        $r->assertSee(end($title));
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_with_taxonomy_wheres_then_taxonomies_are_correct()
    {
        $tax1 = TaxonomyWhere::factory()->create();
        $tax2 = TaxonomyWhere::factory()->create();
        $t = EcTrack::factory()->create();
        $t->taxonomyWheres()->attach([$tax1->id, $tax2->id]);
        $r = $this->get('/track/' . $t->id);
        $r->assertSee($tax1->name);
        $r->assertSee($tax2->name);
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_without_taxonomy_wheres_then_it_returns_200()
    {
        $t = EcTrack::factory()->create();
        if ($t->taxonomyWheres()->count() > 0) {
            foreach ($t->taxonomyWheres() as $tax) {
                $t->detach($tax);
            }
        }
        $r = $this->get('/track/' . $t->id);
        $r->assertStatus(200);
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_without_taxonomy_wheres_then_div_class_taxonomyWheres_does_not_exists()
    {
        $t = EcTrack::factory()->create();
        if ($t->taxonomyWheres()->count() > 0) {
            foreach ($t->taxonomyWheres() as $tax) {
                $t->detach($tax);
            }
        }
        $r = $this->get('/track/' . $t->id);
        $r->assertDontSee('taxonomyWheres');
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_with_taxonomy_activities_then_taxonomies_are_correct()
    {
        $tax1 = TaxonomyActivity::factory()->create();
        $tax2 = TaxonomyActivity::factory()->create();
        $t = EcTrack::factory()->create();
        $t->taxonomyActivities()->attach([$tax1->id, $tax2->id]);
        $r = $this->get('/track/' . $t->id);
        $r->assertSee($tax1->name);
        $r->assertSee($tax2->name);
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_without_taxonomy_activities_then_it_returns_200()
    {
        $t = EcTrack::factory()->create();
        if ($t->taxonomyActivities()->count() > 0) {
            foreach ($t->taxonomyActivities() as $tax) {
                $t->detach($tax);
            }
        }
        $r = $this->get('/track/' . $t->id);
        $r->assertStatus(200);
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_without_taxonomy_activities_then_div_id_taxonomyActivities_does_not_exist()
    {
        $t = EcTrack::factory()->create();
        if ($t->taxonomyActivities()->count() > 0) {
            foreach ($t->taxonomyActivities() as $tax) {
                $t->detach($tax);
            }
        }
        $r = $this->get('/track/' . $t->id);
        $r->assertDontSee('taxonomyActivities');
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_with_taxonomy_activity_with_icon_then_icon_is_correct()
    {
        $tax1 = TaxonomyActivity::factory()->create([
            'icon' => 'webmapp-icon-bike',
        ]);
        $tax2 = TaxonomyActivity::factory()->create([
            'icon' => 'webmapp-icon-filters-outline',
        ]);
        $t = EcTrack::factory()->create();
        $t->taxonomyActivities()->attach([$tax1->id, $tax2->id]);
        $r = $this->get('/track/' . $t->id);
        $r->assertSee($tax1->icon);
        $r->assertSee($tax2->icon);
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_with_taxonomy_activities_without_icon_then_status_200_and_class_activityIcon_does_not_exists()
    {
        $tax1 = TaxonomyActivity::factory()->create();
        $tax2 = TaxonomyActivity::factory()->create();
        $t = EcTrack::factory()->create();
        $t->taxonomyActivities()->attach([$tax1->id, $tax2->id]);
        $r = $this->get('/track/' . $t->id);
        $r->assertStatus(200);
        $r->assertDontSee('activityIcon');
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_with_description_then_description_is_correct()
    {
        $t = EcTrack::factory()->create();
        $r = $this->get('/track/' . $t->id);
        $r->assertSee($t->description);
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_without_description_then_it_returns_200()
    {
        $t = EcTrack::factory()->create([
            'description' => null,
        ]);
        $r = $this->get('/track/' . $t->id);
        $r->assertStatus(200);
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_without_description_then_div_id_trackDescription_does_not_exists()
    {
        $t = EcTrack::factory()->create([
            'description' => null,
        ]);
        $r = $this->get('/track/' . $t->id);
        $r->assertDontSee('trackDescription');
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_with_track_details_then_details_are_correct()
    {
        $t = EcTrack::factory()->create();
        $r = $this->get('/track/' . $t->id);
        $r->assertSee($t->distance . 'km');
        $r->assertSee($t->ascent . 'm');
        $r->assertSee($t->descent . 'm');
        $r->assertSee($t->ele_from . 'm');
        $r->assertSee($t->ele_to . 'm');
        $r->assertSee($t->ele_min . 'm');
        $r->assertSee($t->ele_max . 'm');
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_without_track_details_then_class_trackDetails_does_not_exists()
    {
        $t = EcTrack::factory()->create([
            'distance' => null,
            'ascent' => null,
            'descent' => null,
            'ele_min' => null,
            'ele_max' => null,
            'ele_from' => null,
            'ele_to' => null,
        ]);
        $r = $this->get('/track/' . $t->id);
        $r->assertDontSee('trackDetails');
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_with_gallery_then_gallery_images_are_correct()
    {
        $media1 = EcMedia::factory()->frontEndSizes()->create();
        $media2 = EcMedia::factory()->frontEndSizes()->create();
        $t = EcTrack::factory()->create();
        $t->ecMedia()->attach($media1);
        $t->ecMedia()->attach($media2);
        $t->save();
        $r = $this->get('/track/' . $t->id);
        $r->assertSee($media1->thumbnail('400x200'));
        $r->assertSee($media2->thumbnail('400x200'));
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_without_gallery_then_it_returns_200()
    {
        $t = EcTrack::factory()->create();
        $r = $this->get('/track/' . $t->id);
        $r->assertStatus(200);
    }

    /**
     * @test
     */
    public function when_visitor_access_to_track_without_gallery_then_div_id_carousel_does_not_exist()
    {
        $t = EcTrack::factory()->create();
        $r = $this->get('/track/' . $t->id);
        $r->assertDontSee('carousel');
    }
}
