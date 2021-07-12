<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateValuesToI18nInTaxonomies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('taxonomy_activities')->lazyById()->each(function ($activity) {
            $name = json_encode(['it' => $activity->name, 'en' => $activity->name]);
            $description = json_encode(['it' => $activity->description, 'en' => $activity->description]);
            $excerpt = json_encode(['it' => $activity->excerpt, 'en' => $activity->excerpt]);
            DB::table('taxonomy_activities')
                ->where('id', $activity->id)
                ->update([
                    'name' => $name,
                    'description' => $description,
                    'excerpt' => $excerpt,
                ]);
        });

        DB::table('taxonomy_wheres')->lazyById()->each(function ($where) {
            $name = json_encode(['it' => $where->name, 'en' => $where->name]);
            $description = json_encode(['it' => $where->description, 'en' => $where->description]);
            $excerpt = json_encode(['it' => $where->excerpt, 'en' => $where->excerpt]);
            DB::table('taxonomy_wheres')
                ->where('id', $where->id)
                ->update([
                    'name' => $name,
                    'description' => $description,
                    'excerpt' => $excerpt,
                ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('taxonomy_activities')->lazyById()->each(function ($activity) {
            $name = json_decode($activity->name);
            $description = json_decode($activity->description);
            $excerpt = json_decode($activity->excerpt);
            DB::table('taxonomy_activities')
                ->where('id', $activity->id)
                ->update([
                    'name' => $name->it,
                    'description' => $description->it,
                    'excerpt' => $excerpt->it,
                ]);
        });

        DB::table('taxonomy_wheres')->lazyById()->each(function ($where) {
            $name = json_decode($where->name);
            $description = json_decode($where->description);
            $excerpt = json_decode($where->excerpt);
            DB::table('taxonomy_wheres')
                ->where('id', $where->id)
                ->update([
                    'name' => $name->it,
                    'description' => $description->it,
                    'excerpt' => $excerpt->it,
                ]);
        });
    }
}
