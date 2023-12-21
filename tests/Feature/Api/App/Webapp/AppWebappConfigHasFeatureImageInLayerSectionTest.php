<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcTrack;
use App\Models\Layer;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyTarget;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhen;
use App\Models\TaxonomyWhere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppWebappConfigHasFeatureImageInLayerSectionTest extends TestCase
{
    private $author;

    private $image;

    private $track;

    private $layer;

    private $webapp;

    use RefreshDatabase;

    /** @test */
    public function single_layer_with_theme_taxonomy_has_proper_feature_image()
    {
        $this->prepare();
        $term = TaxonomyTheme::factory()->create(['feature_image' => $this->image->id]);
        $this->track->taxonomyThemes()->attach($term);
        $this->layer->taxonomyThemes()->attach($term);
        $this->check();
    }

    /** @test */
    public function single_layer_with_where_taxonomy_has_proper_feature_image()
    {
        $this->prepare();
        $term = TaxonomyWhere::factory()->create(['feature_image' => $this->image->id]);
        $this->track->taxonomyWheres()->attach($term);
        $this->layer->taxonomyWheres()->attach($term);
        $this->check();
    }

    /** @test */
    public function single_layer_with_activity_taxonomy_has_proper_feature_image()
    {
        $this->prepare();
        $term = TaxonomyActivity::factory()->create(['feature_image' => $this->image->id]);
        $this->track->taxonomyActivities()->attach($term);
        $this->layer->taxonomyActivities()->attach($term);
        $this->check();
    }

    /** @test */
    public function single_layer_with_target_taxonomy_has_proper_feature_image()
    {
        $this->prepare();
        $term = TaxonomyTarget::factory()->create(['feature_image' => $this->image->id]);
        $this->track->taxonomyTargets()->attach($term);
        $this->layer->taxonomyTargets()->attach($term);
        $this->check();
    }

    /** @test */
    public function single_layer_with_when_taxonomy_has_proper_feature_image()
    {
        $this->prepare();
        $term = TaxonomyWhen::factory()->create(['feature_image' => $this->image->id]);
        $this->track->taxonomyWhens()->attach($term);
        $this->layer->taxonomyWhens()->attach($term);
        $this->check();
    }

    /** @test */
    public function single_layer_with_poi_type_taxonomy_has_proper_feature_image()
    {
        $this->prepare();
        $term = TaxonomyPoiType::factory()->create(['feature_image' => $this->image->id]);
        $this->track->taxonomyPoiTypes()->attach($term);
        $this->layer->taxonomyPoiTypes()->attach($term);
        $this->check();
    }

    private function prepare()
    {
        $this->author = User::factory()->create();
        $this->image = EcMedia::factory()->create(['thumbnails' => json_encode([
            '50x50' => 'https://50x50.jpg',
            '400x200' => 'https://400x200.jpg',
        ])]);
        $this->track = EcTrack::factory()->create(['user_id' => $this->author->id]);

        $this->webapp = App::factory()->create(['user_id' => $this->author->id]);

        $this->layer = Layer::factory()->create(['app_id' => $this->webapp->id]);
    }

    private function check()
    {
        Storage::disk('conf')->delete($this->webapp->id.'.json');
        $result = $this->getJson('/api/app/webapp/'.$this->webapp->id.'/config', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->MAP->layers));
        $this->assertIsArray($json->MAP->layers);
        $this->assertEquals(1, count($json->MAP->layers));

        $layer_conf = $json->MAP->layers[0];

        $this->assertTrue(isset($layer_conf->name));
        $this->assertTrue(isset($layer_conf->feature_image));
        $this->assertEquals('https://400x200.jpg', $layer_conf->feature_image);
    }
}
