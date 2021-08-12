<?php

namespace Tests\Unit\Commands\ImportAndSync;

use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Console\Commands\ImportAndSync;
use App\Models\TaxonomyWhere;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncTableTest extends TestCase {
    use RefreshDatabase;

    private function _getHoquServiceProviderMock() {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    public function testSyncTableTaxonomyWhereWhenElementIsMissing() {
        $this->_getHoquServiceProviderMock();
        //Step 0 : Contextual info
        $cmd = new ImportAndSync;
        $import_method = 'test_method';
        $tmp_table_name = "test_" . substr(str_shuffle(MD5(microtime())), 0, 5);
        $model_name = "TaxonomyWhere";
        $source_id_field = "id";
        $mapping = ["name" => "name"];
        TaxonomyWhere::where("import_method", "test_method")->delete();

        //Step 1 : Create tmp table
        $tmp_model = Schema::create($tmp_table_name, function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        //TODO : passare a Eloquent
        DB::insert(DB::raw("INSERT INTO $tmp_table_name ( id, name) VALUES (1, 'test')"));

        //Step 2 : Verify if new element is missing in TaxonomyWhere table
        $this->assertEquals(0, TaxonomyWhere::where('source_id', 1)
            ->where('import_method', 'test_method')
            ->get()
            ->count());

        //Step 3 : Call the method
        $cmd->syncTable($import_method, $tmp_table_name, $model_name, $source_id_field, $mapping);

        //Step 4 : Verify new element is present
        $this->assertEquals(1, TaxonomyWhere::where('source_id', 1)
            ->where('import_method', 'test_method')
            ->get()
            ->count());

        //Step 5 : Clean tmp table
        Schema::dropIfExists($tmp_table_name);
    }

    public function testSyncTableTaxonomyWhereWhenElementIsAlreadyExisting() {
        $this->_getHoquServiceProviderMock();
        //Step 0 : Contextual info
        $cmd = new ImportAndSync;
        $import_method = 'test_method';
        $tmp_table_name = "test_" . substr(str_shuffle(MD5(microtime())), 0, 5);
        $model_name = "TaxonomyWhere";
        $source_id_field = "id";
        $mapping = ["name" => "name"];
        TaxonomyWhere::where("import_method", "test_method")->delete();
        $term = TaxonomyWhere::create(["source_id" => 1, "import_method" => $import_method, "name" => "notTest"]);

        //Step 1 : Create tmp table
        $tmp_model = Schema::create($tmp_table_name, function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        //TODO : passare a Eloquent
        DB::insert(DB::raw("INSERT INTO $tmp_table_name ( id, name) VALUES (1, 'test')"));

        //Step 2 : Call the method
        $cmd->syncTable($import_method, $tmp_table_name, $model_name, $source_id_field, $mapping);
        //Step 3 : Verify new element has been updated
        $term = TaxonomyWhere::where("source_id", '1')->where("import_method", $import_method)->first();
        $this->assertEquals("test", $term->name);

        //Step 4 : Clean tmp table
        Schema::dropIfExists($tmp_table_name);
    }

    public function testSyncTableTaxonomyWhereWithALongTable() {
        $this->_getHoquServiceProviderMock();
        //Step 0 : Contextual info
        $cmd = new ImportAndSync;
        $import_method = 'test_method';
        $tmp_table_name = "test_" . substr(str_shuffle(MD5(microtime())), 0, 5);
        $model_name = "TaxonomyWhere";
        $source_id_field = "id";
        $mapping = ["name" => "name"];
        TaxonomyWhere::where("import_method", "test_method")->delete();

        //Step 1 : Create tmp table
        $tmp_model = Schema::create($tmp_table_name, function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        //TODO : passare a Eloquent
        for ($i = 0; $i < 100; $i++) {
            DB::insert(DB::raw("INSERT INTO $tmp_table_name (name) VALUES ('test')"));
        }

        //Step 2 : Verify if new element is missing in TaxonomyWhere table
        $this->assertEquals(0, TaxonomyWhere::where('import_method', 'test_method')
            ->get()
            ->count());

        //Step 3 : Call the method
        $cmd->syncTable($import_method, $tmp_table_name, $model_name, $source_id_field, $mapping);

        //Step 4 : Verify new element is present
        $this->assertEquals(100, TaxonomyWhere::where('import_method', 'test_method')
            ->get()
            ->count());

        //Step 5 : Clean tmp table
        Schema::dropIfExists($tmp_table_name);
    }
}
