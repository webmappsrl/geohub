<?php

namespace Tests\Unit\Traits;

use App\Traits\ImporterAndSyncTrait;
use Tests\TestCase;

class importer
{
    use ImporterAndSyncTrait;
}

class ImporterAndSyncTraitprepareTagsForPoiWithOsmMappingTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @test
     *
     * @return void
     */
    public function no_properties_empty_array()
    {
        $importer = new importer;
        $tags = $importer->prepareTagsForPoiWithOsmMapping([]);
        $this->assertIsArray($tags);
        $this->assertEquals(0, count($tags));
    }

    /**
     * Test property name
     *
     * @test
     *
     * @return void
     */
    public function with_name_has_translated_it_name()
    {
        $importer = new importer;
        $poi = [];
        $poi['properties']['name'] = 'NAME_IT';
        $tags = $importer->prepareTagsForPoiWithOsmMapping($poi);
        $this->assertIsArray($tags);
        $this->assertArrayHasKey('name', $tags);
        $this->assertEquals($tags['name']['it'], 'NAME_IT');
    }

    /**
     * Test property description
     *
     * @test
     *
     * @return void
     */
    public function with_description_has_translated_it_name()
    {
        $importer = new importer;
        $poi = [];
        $poi['properties']['description'] = 'DESCRIPTION_IT';
        $tags = $importer->prepareTagsForPoiWithOsmMapping($poi);
        $this->assertIsArray($tags);
        $this->assertArrayHasKey('description', $tags);
        $this->assertEquals($tags['description']['it'], 'DESCRIPTION_IT');
    }

    /**
     * Test property description
     *
     * @test
     *
     * @return void
     */
    public function with_flat_properties_has_proper_tags()
    {
        $importer = new importer;
        $poi = [];

        $mapping_flat = [
            'phone' => 'phone',
            'email' => 'email',
            'addr:street' => 'addr_street',
            'addr:housenumber' => 'adrr_housenumber',
            'addr:postcode' => 'adrr_postcode',
            'addr:city' => 'addr_locality',
            'capacity' => 'capacity',
            'stars' => 'stars',
            'ele' => 'ele',
        ];

        $properties = [
            'phone' => 'PHONE',
            'email' => 'EMAIL',
            'addr:street' => 'STREET',
            'addr:housenumber' => 'HOUSENUMBER',
            'addr:postcode' => 'POSTCODE',
            'addr:city' => 'LOCALITY',
            'capacity' => 'CAPACITY',
            'stars' => 'STARS',
            'ele' => 'ELE',
        ];

        $poi = [];
        $poi['properties'] = $properties;

        $tags = $importer->prepareTagsForPoiWithOsmMapping($poi);
        $this->assertIsArray($tags);
        foreach ($mapping_flat as $key => $value) {
            $this->assertArrayHasKey($value, $tags);
        }
        foreach ($properties as $key => $value) {
            $this->assertEquals($value, $tags[$mapping_flat[$key]]);
        }
    }
}
