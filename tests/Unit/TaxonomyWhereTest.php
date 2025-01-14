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
    public function test_is_editable_by_user_interface()
    {
        $this->_getHoquServiceProviderMock();
        $where = new TaxonomyWhere(['name' => 'fake']);
        $this->assertTrue($where->isEditableByUserInterface());
    }

    public function test_is_not_editable_by_user_interface()
    {
        $this->_getHoquServiceProviderMock();
        $where = new TaxonomyWhere(['name' => 'fake', 'import_method' => 'fake']);
        $this->assertFalse($where->isEditableByUserInterface());
    }

    public function test_is_imported_by_external_data()
    {
        $this->_getHoquServiceProviderMock();
        $where = new TaxonomyWhere(['name' => 'fake', 'import_method' => 'fake']);
        $this->assertTrue($where->isImportedByExternalData());
    }

    public function test_is_not_imported_by_external_data()
    {
        $this->_getHoquServiceProviderMock();
        $where = new TaxonomyWhere(['name' => 'fake']);
        $this->assertFalse($where->isImportedByExternalData());
    }

    public function test_save_taxonomy_where_ok()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('update_geomixer_taxonomy_where', ['id' => 1])
                ->andReturn(201);
        });
        $where = new TaxonomyWhere;
        $where->id = 1;
        $where->save();
    }

    public function test_save_taxonomy_where_error()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('update_geomixer_taxonomy_where', ['id' => 1])
                ->andThrows(new Exception);
        });
        Log::shouldReceive('error')
            ->once();
        $where = new TaxonomyWhere;
        $where->id = 1;
        $where->save();
    }
}
