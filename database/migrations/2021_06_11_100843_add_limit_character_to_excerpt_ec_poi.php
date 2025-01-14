<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddLimitCharacterToExcerptEcPoi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE ec_pois  ALTER COLUMN excerpt TYPE VARCHAR(255)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE ec_pois  ALTER COLUMN excerpt TYPE TEXT');
    }
}
