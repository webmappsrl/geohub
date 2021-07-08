<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDurationFromFloatToStringEcTracks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE ec_tracks  ALTER COLUMN duration_forward TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE ec_tracks  ALTER COLUMN duration_backward TYPE VARCHAR(255)');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE ec_tracks  ALTER COLUMN duration_forward TYPE FLOAT USING duration_forward::double precision');
        DB::statement('ALTER TABLE ec_tracks  ALTER COLUMN duration_backward TYPE FLOAT USING duration_forward::double precision');

    }
}
