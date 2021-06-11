<?php

namespace Tests\Unit;

use App\Models\TaxonomyWhere;
use App\Providers\HoquServiceProvider;
use Doctrine\DBAL\Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TaxonomyWhereTest extends TestCase
{
    use RefreshDatabase;

    private function _getHoquServiceProviderMock()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */

    public function testIsEditableByUserInterface()
    {
        $this->_getHoquServiceProviderMock();
        $where = new TaxonomyWhere(array('name' => 'fake'));
        $this->assertTrue($where->isEditableByUserInterface());
    }

    public function testIsNotEditableByUserInterface()
    {
        $this->_getHoquServiceProviderMock();
        $where = new TaxonomyWhere(array('name' => 'fake', 'import_method' => 'fake'));
        $this->assertFalse($where->isEditableByUserInterface());
    }

    public function testIsImportedByExternalData()
    {
        $this->_getHoquServiceProviderMock();
        $where = new TaxonomyWhere(array('name' => 'fake', 'import_method' => 'fake'));
        $this->assertTrue($where->isImportedByExternalData());
    }

    public function testIsNotImportedByExternalData()
    {
        $this->_getHoquServiceProviderMock();
        $where = new TaxonomyWhere(array('name' => 'fake'));
        $this->assertFalse($where->isImportedByExternalData());
    }

    public function testSaveTaxonomyWhereOk()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('update_geomixer_taxonomy_where', ['id' => 1])
                ->andReturn(201);
        });
        $where = new TaxonomyWhere();
        $where->id = 1;
        $where->save();
    }

    public function testSaveTaxonomyWhereError()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('update_geomixer_taxonomy_where', ['id' => 1])
                ->andThrows(new Exception());
        });
        Log::shouldReceive('error')
            ->once();
        $where = new TaxonomyWhere();
        $where->id = 1;
        $where->save();
    }
}
