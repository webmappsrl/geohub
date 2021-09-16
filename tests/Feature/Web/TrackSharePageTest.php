<?php

namespace Tests\Feature;

use App\Models\EcTrack;
use App\Models\EcMedia;
use App\Models\TaxonomyWhere;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TrackSharePageTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function when_visitor_access_to_track_with_not_existing_id_then_it_returns_404()
    {   
        EcTrack::truncate();
        $track = EcTrack::factory()->create();
        $response = $this->get('/track/'.(intval($track->id) + 1));
        $response->assertStatus(404);
    }
    /**
     * @test
     */
    public function when_visitor_access_to_track_with_existing_id_then_it_returns_200()
    {
        $track = EcTrack::factory()->create();
        $response = $this->get('/track/'.$track->id);
        $response->assertStatus(200);
    }
    /**
     * @test
     */
    public function when_visitor_access_to_track_then_the_title_is_correct()
    {
        $track = EcTrack::factory()->create();
        $response = $this->get('/track/'.$track->id);
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
        $r = $this->get('/track/'.$t->id);
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
        $r = $this->get('/track/'.$t->id);
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
        $r = $this->get('/track/'.$t->id);
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
        $t->taxonomyWheres()->attach([$tax1->id,$tax2->id]);
        $r = $this->get('/track/'.$t->id);
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
        $r = $this->get('/track/'.$t->id);
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
        $r = $this->get('/track/'.$t->id);
        $r->assertDontSee('taxonomyWheres');
    }
    /**
     * @test
     */
    public function when_visitor_access_to_track_with_taxonomy_activities_then_taxonomies_are_correct()
    {

    }
    /**
     * @test
     */
    public function when_visitor_access_to_track_without_taxonomy_activities_then_it_returns_200()
    {

    }
    /**
     * @test
     */
    public function when_visitor_access_to_track_without_taxonomy_activities_then_div_id_taxonomyActivities_does_not_exist()
    {

    }
    /**
     * @test
     */
    public function when_visitor_access_to_track_with_description_then_description_is_correct()
    {

    }
    /**
     * @test
     */
    public function when_visitor_access_to_track_without_description_then_it_returns_200()
    {

    }
    /**
     * @test
     */
    public function when_visitor_access_to_track_without_description_then_div_id_trackDescription_is_empty()
    {

    }
    /**
     * @test
     */
    public function when_visitor_access_to_track_with_track_details_then_details_are_correct()
    {

    }
    /**
     * @test
     */
    public function when_visitor_access_to_track_with_gallery_then_gallery_images_are_correct()
    {

    }
    /**
     * @test
     */
    public function when_visitor_access_to_track_without_gallery_then_it_returns_200()
    {

    }
    /**
     * @test
     */
    public function when_visitor_access_to_track_without_gallery_then_div_id_carousel_does_not_exist()
    {

    }
}
