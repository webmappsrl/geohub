<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangeExcerptToTextInEcPois extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE ec_pois ALTER COLUMN excerpt TYPE TEXT USING excerpt::text');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE ec_pois ALTER COLUMN excerpt TYPE VARCHAR(255)');
    }
}
