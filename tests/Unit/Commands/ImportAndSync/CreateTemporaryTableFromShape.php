<?php

namespace Tests\Unit\Commands\ImportAndSync;

use App\Console\Commands\ImportAndSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreateTemporaryTableFromShape extends TestCase
{
    public function test_point()
    {
        // Singolo Punto in PISA
        //  {"type":"Point","coordinates":[10.400619507,43.718016142]}
        // SELECT ST_asgeojson(geom) from rm_1234;

        $shape = dirname(__FILE__).'/testdata/SHAPE/POINT';
        $cmd = new ImportAndSync;
        $table = $cmd->createTemporaryTableFromShape($shape, '4326:4326');
        // Check name field
        $sel = DB::select('select name from '.$table);
        $this->assertTrue(is_array($sel));
        $this->assertEquals(1, count($sel));
        $this->assertEquals('Pisa', substr($sel[0]->name, 0, 4));
        // Check Point Coordinates
        $sel = DB::select('select ST_asgeojson(geom) from '.$table);
        $geom = json_decode($sel[0]->st_asgeojson);
        $this->assertEquals('Point', $geom->type);
        $this->assertEquals(2, count($geom->coordinates));
        $this->assertLessThan(1, abs(10.4 - $geom->coordinates[0]));
        $this->assertLessThan(1, abs(43.7 - $geom->coordinates[1]));

        Schema::dropIfExists($table);

    }

    public function test_multi_line_string()
    {
        // Line in PISA Length 574 m

        $shape = dirname(__FILE__).'/testdata/SHAPE/POLYLINE';
        $cmd = new ImportAndSync;
        $table = $cmd->createTemporaryTableFromShape($shape, '4326:4326');

        // Check line's length
        $sel = DB::select('select floor(ST_length(geom,true)) as length from '.$table);
        $this->assertTrue(is_array($sel));
        $this->assertEquals(574, $sel[0]->length);

        // Check LineString type
        $sel = DB::select('select ST_asgeojson(geom) from '.$table);
        $geom = json_decode($sel[0]->st_asgeojson);
        $this->assertEquals('MultiLineString', $geom->type);
        $this->assertTrue(isset($geom->coordinates));

        Schema::dropIfExists($table);

    }

    public function test_multi_polygon()
    {
        // House in PISA Area 379 m2

        $shape = dirname(__FILE__).'/testdata/SHAPE/POLYGON';
        $cmd = new ImportAndSync;
        $table = $cmd->createTemporaryTableFromShape($shape, '4326:4326');

        // Check line's length
        $sel = DB::select('select floor(ST_area(geom,true)) as area from '.$table);
        $this->assertTrue(is_array($sel));
        $this->assertEquals(379, $sel[0]->area);

        // Check LineString type
        $sel = DB::select('select ST_asgeojson(geom) from '.$table);
        $geom = json_decode($sel[0]->st_asgeojson);
        $this->assertEquals('MultiPolygon', $geom->type);
        $this->assertTrue(isset($geom->coordinates));

        Schema::dropIfExists($table);

    }
}
