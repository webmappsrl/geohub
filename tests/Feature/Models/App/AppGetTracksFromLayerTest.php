<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\EcTrack;
use App\Models\Layer;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AppGetTracksFromLayerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function no_layer_empty_array() {
        $app = App::factory()->create();
        $this->assertEquals(0,$app->layers->count());

        $this->assertIsArray($app->getTracksFromLayer());
        $this->assertEquals(0,count($app->getTracksFromLayer()));
    }

    /** @test */
    public function single_layer_with_single_matching_track_with_theme_same_author() {

        $author = User::factory()->create();
        
        $theme_term = TaxonomyTheme::factory()->create();
        
        $track = EcTrack::factory()->create(['user_id'=>$author->id]);
        $track->taxonomyThemes()->attach($theme_term);
        
        $app = App::factory()->create(['user_id'=>$author->id]);

        $layer = Layer::factory()->create(['app_id'=>$app->id]);
        $layer->taxonomyThemes()->attach($theme_term);

        $this->assertIsArray($app->getTracksFromLayer());
        $this->assertEquals(1,count($app->getTracksFromLayer()));
        $this->assertArrayHasKey($track->id,$app->getTracksFromLayer());
        $tracks = $app->getTracksFromLayer();
        $this->assertIsArray($tracks[$track->id]);
        $this->assertEquals(1,count($tracks[$track->id]));
        $this->assertEquals($layer->id,$tracks[$track->id][0]);
    }

    /** @test */
    public function single_layer_with_single_matching_track_with_activity_same_author() {

        $author = User::factory()->create();
        
        $term = TaxonomyActivity::factory()->create();
        
        $track = EcTrack::factory()->create(['user_id'=>$author->id]);
        $track->taxonomyActivities()->attach($term);
        
        $app = App::factory()->create(['user_id'=>$author->id]);

        $layer = Layer::factory()->create(['app_id'=>$app->id]);
        $layer->taxonomyActivities()->attach($term);

        $this->assertIsArray($app->getTracksFromLayer());
        $this->assertEquals(1,count($app->getTracksFromLayer()));
        $this->assertArrayHasKey($track->id,$app->getTracksFromLayer());
        $tracks = $app->getTracksFromLayer();
        $this->assertIsArray($tracks[$track->id]);
        $this->assertEquals(1,count($tracks[$track->id]));
        $this->assertEquals($layer->id,$tracks[$track->id][0]);
    }

    /** @test */
    public function single_layer_with_single_matching_track_with_where_same_author() {

        $author = User::factory()->create();
        
        $term = TaxonomyWhere::factory()->create();
        
        $track = EcTrack::factory()->create(['user_id'=>$author->id]);
        $track->taxonomyWheres()->attach($term);
        
        $app = App::factory()->create(['user_id'=>$author->id]);

        $layer = Layer::factory()->create(['app_id'=>$app->id]);
        $layer->taxonomyWheres()->attach($term);

        $this->assertIsArray($app->getTracksFromLayer());
        $this->assertEquals(1,count($app->getTracksFromLayer()));
        $this->assertArrayHasKey($track->id,$app->getTracksFromLayer());
        $tracks = $app->getTracksFromLayer();
        $this->assertIsArray($tracks[$track->id]);
        $this->assertEquals(1,count($tracks[$track->id]));
        $this->assertEquals($layer->id,$tracks[$track->id][0]);
    }
    /** @test */
    public function single_layer_with_two_matching_tracks_with_theme_same_author() {
        $author = User::factory()->create();
        
        $theme_term = TaxonomyTheme::factory()->create();
        
        $track1 = EcTrack::factory()->create(['user_id'=>$author->id]);
        $track1->taxonomyThemes()->attach($theme_term);

        $track2 = EcTrack::factory()->create(['user_id'=>$author->id]);
        $track2->taxonomyThemes()->attach($theme_term);
        
        $app = App::factory()->create(['user_id'=>$author->id]);

        $layer = Layer::factory()->create(['app_id'=>$app->id]);
        $layer->taxonomyThemes()->attach($theme_term);

        $this->assertIsArray($app->getTracksFromLayer());
        $this->assertEquals(2,count($app->getTracksFromLayer()));

        $this->assertArrayHasKey($track1->id,$app->getTracksFromLayer());
        $this->assertArrayHasKey($track2->id,$app->getTracksFromLayer());

        $tracks = $app->getTracksFromLayer();
        $this->assertIsArray($tracks[$track1->id]);
        $this->assertEquals(1,count($tracks[$track1->id]));
        $this->assertEquals($layer->id,$tracks[$track1->id][0]);

        $this->assertIsArray($tracks[$track2->id]);
        $this->assertEquals(1,count($tracks[$track2->id]));
        $this->assertEquals($layer->id,$tracks[$track2->id][0]);

    }

    /** @test */
    public function two_layers_with_same_maching_track_with_theme_same_author() {
        $author = User::factory()->create();
        
        $theme_term = TaxonomyTheme::factory()->create();
        
        $track = EcTrack::factory()->create(['user_id'=>$author->id]);
        $track->taxonomyThemes()->attach($theme_term);
        
        $app = App::factory()->create(['user_id'=>$author->id]);

        $layer1 = Layer::factory()->create(['app_id'=>$app->id]);
        $layer1->taxonomyThemes()->attach($theme_term);

        $layer2 = Layer::factory()->create(['app_id'=>$app->id]);
        $layer2->taxonomyThemes()->attach($theme_term);

        $this->assertIsArray($app->getTracksFromLayer());
        $this->assertEquals(1,count($app->getTracksFromLayer()));
        $this->assertArrayHasKey($track->id,$app->getTracksFromLayer());

        $tracks = $app->getTracksFromLayer();
        $this->assertIsArray($tracks[$track->id]);
        $this->assertEquals(2,count($tracks[$track->id]));
        $this->assertTrue(in_array($layer1->id,$tracks[$track->id]));
        $this->assertTrue(in_array($layer2->id,$tracks[$track->id]));

    }


}
