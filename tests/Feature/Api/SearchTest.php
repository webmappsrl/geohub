<?php

namespace Tests\Feature\Api;

use App\Models\EcTrack;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyWhere;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    public function test_api_is_reachable() {
        $response = $this->postJson('/api/search', []);

        $response->assertStatus(400); // No params specified
    }

    public function test_api_return_empty_values_when_no_elements_available() {
        $response = $this->postJson('/api/search', [
            'string' => 'test',
            'language' => 'it'
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json);
        $this->assertCount(3, $json);
        $this->assertArrayHasKey('places', $json);
        $this->assertIsArray($json['places']);
        $this->assertCount(0, $json['places']);
        $this->assertArrayHasKey('ec_tracks', $json);
        $this->assertIsArray($json['ec_tracks']);
        $this->assertCount(0, $json['ec_tracks']);
        $this->assertArrayHasKey('poi_types', $json);
        $this->assertIsArray($json['poi_types']);
        $this->assertCount(0, $json['poi_types']);
    }

    public function test_api_return_empty_values_when_no_elements_match() {
        TaxonomyWhere::factory([
            'name' => [
                'it' => 'prova'
            ]
        ])->create();
        EcTrack::factory([
            'name' => [
                'it' => 'prova'
            ]
        ])->create();
        TaxonomyPoiType::factory([
            'name' => [
                'it' => 'prova'
            ]
        ])->create();
        $response = $this->postJson('/api/search', [
            'string' => 'test',
            'language' => 'it'
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json);
        $this->assertCount(3, $json);
        $this->assertArrayHasKey('places', $json);
        $this->assertIsArray($json['places']);
        $this->assertCount(0, $json['places']);
        $this->assertArrayHasKey('ec_tracks', $json);
        $this->assertIsArray($json['ec_tracks']);
        $this->assertCount(0, $json['ec_tracks']);
        $this->assertArrayHasKey('poi_types', $json);
        $this->assertIsArray($json['poi_types']);
        $this->assertCount(0, $json['poi_types']);
    }

    public function test_api_return_matching_values() {
        TaxonomyWhere::factory([
            'name' => [
                'it' => 'testa'
            ]
        ])->create();
        EcTrack::factory([
            'name' => [
                'it' => 'testata'
            ]
        ])->create();
        TaxonomyPoiType::factory([
            'name' => [
                'it' => 'testing'
            ]
        ])->create();
        $response = $this->postJson('/api/search', [
            'string' => 'test',
            'language' => 'it'
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json);
        $this->assertCount(3, $json);
        $this->assertArrayHasKey('places', $json);
        $this->assertIsArray($json['places']);
        $this->assertCount(1, $json['places']);
        $this->assertArrayHasKey('ec_tracks', $json);
        $this->assertIsArray($json['ec_tracks']);
        $this->assertCount(1, $json['ec_tracks']);
        $this->assertArrayHasKey('poi_types', $json);
        $this->assertIsArray($json['poi_types']);
        $this->assertCount(1, $json['poi_types']);
    }

    public function test_api_return_matching_values_in_same_language() {
        TaxonomyWhere::factory([
            'name' => [
                'it' => 'testa',
                'en' => 'try'
            ]
        ])->create();
        EcTrack::factory([
            'name' => [
                'it' => 'testata',
                'en' => 'test'
            ]
        ])->create();
        TaxonomyPoiType::factory([
            'name' => [
                'it' => 'testing',
                'en' => 'sentiero'
            ]
        ])->create();
        $response = $this->postJson('/api/search', [
            'string' => 'test',
            'language' => 'en'
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json);
        $this->assertCount(3, $json);
        $this->assertArrayHasKey('places', $json);
        $this->assertIsArray($json['places']);
        $this->assertCount(0, $json['places']);
        $this->assertArrayHasKey('ec_tracks', $json);
        $this->assertIsArray($json['ec_tracks']);
        $this->assertCount(1, $json['ec_tracks']);
        $this->assertArrayHasKey('poi_types', $json);
        $this->assertIsArray($json['poi_types']);
        $this->assertCount(0, $json['poi_types']);
    }

    public function test_api_return_matching_values_with_correct_mapping() {
        $where = TaxonomyWhere::factory([
            'name' => [
                'it' => 'testa'
            ]
        ])->create();
        $track = EcTrack::factory([
            'name' => [
                'it' => 'testata'
            ]
        ])->has(TaxonomyWhere::factory())->create();
        $poiType = TaxonomyPoiType::factory([
            'name' => [
                'it' => 'testing'
            ]
        ])->create();
        $response = $this->postJson('/api/search', [
            'string' => 'test',
            'language' => 'it'
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json);
        $this->assertCount(3, $json);
        $this->assertArrayHasKey('places', $json);
        $this->assertIsArray($json['places']);
        $this->assertCount(1, $json['places']);
        $this->assertIsArray($json['places'][0]);
        $this->assertArrayHasKey('id', $json['places'][0]);
        $this->assertEquals($where->id, $json['places'][0]['id']);
        $this->assertArrayHasKey('name', $json['places'][0]);
        $this->assertEquals($where->getTranslation('name', 'it'), $json['places'][0]['name']);
        $this->assertArrayHasKey('bbox', $json['places'][0]);
        $this->assertEquals($where->bbox(), $json['places'][0]['bbox']);

        $this->assertArrayHasKey('ec_tracks', $json);
        $this->assertIsArray($json['ec_tracks']);
        $this->assertCount(1, $json['ec_tracks']);
        $this->assertArrayHasKey('id', $json['ec_tracks'][0]);
        $this->assertEquals($track->id, $json['ec_tracks'][0]['id']);
        $this->assertArrayHasKey('name', $json['ec_tracks'][0]);
        $this->assertEquals($track->getTranslation('name', 'it'), $json['ec_tracks'][0]['name']);
        $this->assertArrayHasKey('where', $json['ec_tracks'][0]);
        $this->assertEquals($track->taxonomyWheres()->pluck('id')->toArray(), $json['ec_tracks'][0]['where']);

        $this->assertArrayHasKey('poi_types', $json);
        $this->assertIsArray($json['poi_types']);
        $this->assertCount(1, $json['poi_types']);
        $this->assertArrayHasKey('id', $json['poi_types'][0]);
        $this->assertEquals($poiType->id, $json['poi_types'][0]['id']);
        $this->assertArrayHasKey('name', $json['poi_types'][0]);
        $this->assertEquals($poiType->getTranslation('name', 'it'), $json['poi_types'][0]['name']);
    }
}
