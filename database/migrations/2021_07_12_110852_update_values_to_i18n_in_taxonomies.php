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
            $name = "" != $activity->name ? json_encode(['it' => $activity->name, 'en' => $activity->name]) : null;
            $description = "" != $activity->description ? json_encode(['it' => $activity->description, 'en' => $activity->description]) : null;
            $excerpt = "" != $activity->excerpt ? json_encode(['it' => $activity->excerpt, 'en' => $activity->excerpt]) : null;
            DB::table('taxonomy_activities')
                ->where('id', $activity->id)
                ->update([
                    'name' => $name,
                    'description' => $description,
                    'excerpt' => $excerpt,
                ]);
        });

        DB::table('taxonomy_wheres')->lazyById()->each(function ($where) {
            $name = "" != $where->name ? json_encode(['it' => $where->name, 'en' => $where->name]) : null;
            $description = "" != $where->description ? json_encode(['it' => $where->description, 'en' => $where->description]) : null;
            $excerpt = "" != $where->excerpt ? json_encode(['it' => $where->excerpt, 'en' => $where->excerpt]) : null;
            DB::table('taxonomy_wheres')
                ->where('id', $where->id)
                ->update([
                    'name' => $name,
                    'description' => $description,
                    'excerpt' => $excerpt,
                ]);
        });

        DB::table('taxonomy_whens')->lazyById()->each(function ($when) {
            $name = "" != $when->name ? json_encode(['it' => $when->name, 'en' => $when->name]) : null;
            $description = "" != $when->description ? json_encode(['it' => $when->description, 'en' => $when->description]) : null;
            $excerpt = "" != $when->excerpt ? json_encode(['it' => $when->excerpt, 'en' => $when->excerpt]) : null;
            DB::table('taxonomy_whens')
                ->where('id', $when->id)
                ->update([
                    'name' => $name,
                    'description' => $description,
                    'excerpt' => $excerpt,
                ]);
        });

        DB::table('taxonomy_targets')->lazyById()->each(function ($target) {
            $name = "" != $target->name ? json_encode(['it' => $target->name, 'en' => $target->name]) : null;
            $description = "" != $target->description ? json_encode(['it' => $target->description, 'en' => $target->description]) : null;
            $excerpt = "" != $target->excerpt ? json_encode(['it' => $target->excerpt, 'en' => $target->excerpt]) : null;
            DB::table('taxonomy_targets')
                ->where('id', $target->id)
                ->update([
                    'name' => $name,
                    'description' => $description,
                    'excerpt' => $excerpt,
                ]);
        });

        DB::table('taxonomy_themes')->lazyById()->each(function ($theme) {
            $name = "" != $theme->name ? json_encode(['it' => $theme->name, 'en' => $theme->name]) : null;
            $description = "" != $theme->description ? json_encode(['it' => $theme->description, 'en' => $theme->description]) : null;
            $excerpt = "" != $theme->excerpt ? json_encode(['it' => $theme->excerpt, 'en' => $theme->excerpt]) : null;
            DB::table('taxonomy_themes')
                ->where('id', $theme->id)
                ->update([
                    'name' => $name,
                    'description' => $description,
                    'excerpt' => $excerpt,
                ]);
        });

        DB::table('taxonomy_poi_types')->lazyById()->each(function ($poiType) {
            $name = "" != $poiType->name ? json_encode(['it' => $poiType->name, 'en' => $poiType->name]) : null;
            $description = "" != $poiType->description ? json_encode(['it' => $poiType->description, 'en' => $poiType->description]) : null;
            $excerpt = "" != $poiType->excerpt ? json_encode(['it' => $poiType->excerpt, 'en' => $poiType->excerpt]) : null;
            DB::table('taxonomy_poi_types')
                ->where('id', $poiType->id)
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
                    'name' => isset($name) ? $name->it : null,
                    'description' => isset($description) ? $description->it : null,
                    'excerpt' => isset($excerpt) ? $excerpt->it : null,
                ]);
        });

        DB::table('taxonomy_wheres')->lazyById()->each(function ($where) {
            $name = json_decode($where->name);
            $description = json_decode($where->description);
            $excerpt = json_decode($where->excerpt);
            DB::table('taxonomy_wheres')
                ->where('id', $where->id)
                ->update([
                    'name' => isset($name) ? $name->it : null,
                    'description' => isset($description) ? $description->it : null,
                    'excerpt' => isset($excerpt) ? $excerpt->it : null,
                ]);
        });

        DB::table('taxonomy_whens')->lazyById()->each(function ($when) {
            $name = json_decode($when->name);
            $description = json_decode($when->description);
            $excerpt = json_decode($when->excerpt);
            DB::table('taxonomy_whens')
                ->where('id', $when->id)
                ->update([
                    'name' => isset($name) ? $name->it : null,
                    'description' => isset($description) ? $description->it : null,
                    'excerpt' => isset($excerpt) ? $excerpt->it : null,
                ]);
        });

        DB::table('taxonomy_targets')->lazyById()->each(function ($target) {
            $name = json_decode($target->name);
            $description = json_decode($target->description);
            $excerpt = json_decode($target->excerpt);
            DB::table('taxonomy_targets')
                ->where('id', $target->id)
                ->update([
                    'name' => isset($name) ? $name->it : null,
                    'description' => isset($description) ? $description->it : null,
                    'excerpt' => isset($excerpt) ? $excerpt->it : null,
                ]);
        });

        DB::table('taxonomy_themes')->lazyById()->each(function ($theme) {
            $name = json_decode($theme->name);
            $description = json_decode($theme->description);
            $excerpt = json_decode($theme->excerpt);
            DB::table('taxonomy_themes')
                ->where('id', $theme->id)
                ->update([
                    'name' => isset($name) ? $name->it : null,
                    'description' => isset($description) ? $description->it : null,
                    'excerpt' => isset($excerpt) ? $excerpt->it : null,
                ]);
        });

        DB::table('taxonomy_poi_types')->lazyById()->each(function ($poiType) {
            $name = json_decode($poiType->name);
            $description = json_decode($poiType->description);
            $excerpt = json_decode($poiType->excerpt);
            DB::table('taxonomy_poi_types')
                ->where('id', $poiType->id)
                ->update([
                    'name' => isset($name) ? $name->it : null,
                    'description' => isset($description) ? $description->it : null,
                    'excerpt' => isset($excerpt) ? $excerpt->it : null,
                ]);
        });
    }
}
