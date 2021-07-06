<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Add3dGeometryToEcTracks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = 'ALTER TABLE ec_tracks
                ALTER COLUMN geometry TYPE geometry(LineStringZ)
                USING geometry::geometry(LineStringZ);';
        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $sql = 'ALTER TABLE ec_tracks
                ALTER COLUMN geometry TYPE geometry(LineString)
                USING ST_force2D(geometry);';
        DB::statement($sql);
    }
}
