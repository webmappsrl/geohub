<?php

namespace Tests\Unit\Commands\ImportAndSync;

use App\Console\Commands\ImportAndSync;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddAdministrativeLevelToTemporaryTableTest extends TestCase
{
    public function testTableWithMoreThanOneElementsWithoutAdminLevelColumn()
    {
        // Create tmp table
        $tmp_table_name = 'test_'.substr(str_shuffle(md5(microtime())), 0, 5);
        Schema::create($tmp_table_name, function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        //TODO : passare a Eloquent
        for ($i = 0; $i < 100; $i++) {
            DB::insert(DB::raw("INSERT INTO $tmp_table_name (name) VALUES ('test')"));
        }
        // Call function
        $cmd = new ImportAndSync();
        $cmd->addAdministrativeLevelToTemporaryTable(1, $tmp_table_name);

        // CHECK
        $this->assertEquals(100, DB::table($tmp_table_name)->where('admin_level', 1)->get()->count());
    }

    public function testTableWithMoreThanOneElementsWithAdminLevelColumn()
    {
        // Create tmp table
        $tmp_table_name = 'test_'.substr(str_shuffle(md5(microtime())), 0, 5);
        Schema::create($tmp_table_name, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('admin_level');
        });

        //TODO : passare a Eloquent
        for ($i = 0; $i < 100; $i++) {
            DB::insert(DB::raw("INSERT INTO $tmp_table_name (name,admin_level) VALUES ('test',2)"));
        }
        // Call function
        $cmd = new ImportAndSync();
        $cmd->addAdministrativeLevelToTemporaryTable(1, $tmp_table_name);

        // CHECK
        $this->assertEquals(100, DB::table($tmp_table_name)->where('admin_level', 1)->get()->count());
    }
}
