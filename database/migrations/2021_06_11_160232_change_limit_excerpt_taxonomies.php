<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeLimitExcerptTaxonomies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE taxonomy_activities  ALTER COLUMN excerpt TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE taxonomy_whens  ALTER COLUMN excerpt TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE taxonomy_wheres  ALTER COLUMN excerpt TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE taxonomy_themes  ALTER COLUMN excerpt TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE taxonomy_poi_types  ALTER COLUMN excerpt TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE taxonomy_targets  ALTER COLUMN excerpt TYPE VARCHAR(255)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE taxonomy_activities  ALTER COLUMN excerpt TYPE TEXT');
        DB::statement('ALTER TABLE taxonomy_whens  ALTER COLUMN excerpt TYPE TEXT');
        DB::statement('ALTER TABLE taxonomy_wheres  ALTER COLUMN excerpt TYPE TEXT');
        DB::statement('ALTER TABLE taxonomy_themes  ALTER COLUMN excerpt TYPE TEXT');
        DB::statement('ALTER TABLE taxonomy_poi_types  ALTER COLUMN excerpt TYPE TEXT');
        DB::statement('ALTER TABLE taxonomy_targets  ALTER COLUMN excerpt TYPE TEXT');
    }
}
