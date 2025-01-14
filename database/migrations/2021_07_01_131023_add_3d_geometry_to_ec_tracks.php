<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
                USING ST_force3D(geometry::geometry);';
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
