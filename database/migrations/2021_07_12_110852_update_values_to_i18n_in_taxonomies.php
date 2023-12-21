<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('taxonomy_activities')->lazyById()->each(function ($activity) {
            $updateActivities = [];
            if ($activity->name != null) {
                $name = json_encode(['it' => $activity->name, 'en' => $activity->name]);
                $updateActivities['name'] = $name;
            }

            if ($activity->description != null) {
                $description = json_encode(['it' => $activity->description, 'en' => $activity->description]);
                $updateActivities['description'] = $description;
            }

            if ($activity->excerpt != null) {
                $excerpt = json_encode(['it' => $activity->excerpt, 'en' => $activity->excerpt]);
                $updateActivities['excerpt'] = $excerpt;
            }

            if (count($updateActivities)) {
                DB::table('taxonomy_activities')
                    ->where('id', $activity->id)
                    ->update($updateActivities);
            }
        });

        DB::table('taxonomy_wheres')->lazyById()->each(function ($where) {
            $updateWheres = [];
            if ($where->name != null) {
                $name = json_encode(['it' => $where->name, 'en' => $where->name]);
                $updateWheres['name'] = $name;
            }

            if ($where->description != null) {
                $description = json_encode(['it' => $where->description, 'en' => $where->description]);
                $updateWheres['description'] = $description;
            }

            if ($where->excerpt != null) {
                $excerpt = json_encode(['it' => $where->excerpt, 'en' => $where->excerpt]);
                $updateWheres['excerpt'] = $excerpt;
            }

            if (count($updateWheres)) {
                DB::table('taxonomy_wheres')
                    ->where('id', $where->id)
                    ->update($updateWheres);
            }
        });

        DB::table('taxonomy_whens')->lazyById()->each(function ($when) {
            $updateWhens = [];
            if ($when->name != null) {
                $name = json_encode(['it' => $when->name, 'en' => $when->name]);
                $updateWhens['name'] = $name;
            }

            if ($when->description != null) {
                $description = json_encode(['it' => $when->description, 'en' => $when->description]);
                $updateWhens['description'] = $description;
            }

            if ($when->excerpt != null) {
                $excerpt = json_encode(['it' => $when->excerpt, 'en' => $when->excerpt]);
                $updateWhens['excerpt'] = $excerpt;
            }

            if (count($updateWhens)) {
                DB::table('taxonomy_whens')
                    ->where('id', $when->id)
                    ->update($updateWhens);
            }
        });

        DB::table('taxonomy_targets')->lazyById()->each(function ($target) {
            $updateTargets = [];
            if ($target->name != null) {
                $name = json_encode(['it' => $target->name, 'en' => $target->name]);
                $updateTargets['name'] = $name;
            }

            if ($target->description != null) {
                $description = json_encode(['it' => $target->description, 'en' => $target->description]);
                $updateTargets['description'] = $description;
            }

            if ($target->excerpt != null) {
                $excerpt = json_encode(['it' => $target->excerpt, 'en' => $target->excerpt]);
                $updateTargets['excerpt'] = $excerpt;
            }

            if (count($updateTargets)) {
                DB::table('taxonomy_targets')
                    ->where('id', $target->id)
                    ->update($updateTargets);
            }
        });

        DB::table('taxonomy_themes')->lazyById()->each(function ($theme) {
            $updateThemes = [];
            if ($theme->name != null) {
                $name = json_encode(['it' => $theme->name, 'en' => $theme->name]);
                $updateThemes['name'] = $name;
            }

            if ($theme->description != null) {
                $description = json_encode(['it' => $theme->description, 'en' => $theme->description]);
                $updateThemes['description'] = $description;
            }

            if ($theme->excerpt != null) {
                $excerpt = json_encode(['it' => $theme->excerpt, 'en' => $theme->excerpt]);
                $updateThemes['excerpt'] = $excerpt;
            }

            if (count($updateThemes)) {
                DB::table('taxonomy_themes')
                    ->where('id', $theme->id)
                    ->update($updateThemes);
            }
        });

        DB::table('taxonomy_poi_types')->lazyById()->each(function ($poiType) {
            $updatePoiTypes = [];
            if ($poiType->name != null) {
                $name = json_encode(['it' => $poiType->name, 'en' => $poiType->name]);
                $updatePoiTypes['name'] = $name;
            }

            if ($poiType->description != null) {
                $description = json_encode(['it' => $poiType->description, 'en' => $poiType->description]);
                $updatePoiTypes['description'] = $description;
            }

            if ($poiType->excerpt != null) {
                $excerpt = json_encode(['it' => $poiType->excerpt, 'en' => $poiType->excerpt]);
                $updatePoiTypes['excerpt'] = $excerpt;
            }

            if (count($updatePoiTypes)) {
                DB::table('taxonomy_poi_types')
                    ->where('id', $poiType->id)
                    ->update($updatePoiTypes);
            }
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
            $updateActivities = [];
            if ($activity->name != null) {
                $name = json_decode($activity->name);
                if (isset($name)) {
                    $updateActivities['name'] = $name->it;
                }
            }

            if ($activity->description != null) {
                $description = json_decode($activity->description);
                if (isset($description)) {
                    $updateActivities['description'] = $description->it;
                }
            }

            if ($activity->excerpt != null) {
                $excerpt = json_decode($activity->excerpt);
                if (isset($excerpt)) {
                    $updateActivities['excerpt'] = $excerpt->it;
                }
            }

            if (count($updateActivities)) {
                DB::table('taxonomy_activities')
                    ->where('id', $activity->id)
                    ->update($updateActivities);
            }
        });

        DB::table('taxonomy_wheres')->lazyById()->each(function ($where) {
            $updateWheres = [];
            if ($where->name != null) {
                $name = json_decode($where->name);
                if (isset($name)) {
                    $updateWheres['name'] = $name->it;
                }
            }

            if ($where->description != null) {
                $description = json_decode($where->description);
                if (isset($description)) {
                    $updateWheres['description'] = $description->it;
                }
            }

            if ($where->excerpt != null) {
                $excerpt = json_decode($where->excerpt);
                if (isset($excerpt)) {
                    $updateWheres['excerpt'] = $excerpt->it;
                }
            }

            if (count($updateWheres)) {
                DB::table('taxonomy_wheres')
                    ->where('id', $where->id)
                    ->update($updateWheres);
            }
        });

        DB::table('taxonomy_whens')->lazyById()->each(function ($when) {
            $updateWhens = [];
            if ($when->name != null) {
                $name = json_decode($when->name);
                if (isset($name)) {
                    $updateWhens['name'] = $name->it;
                }
            }

            if ($when->description != null) {
                $description = json_decode($when->description);
                if (isset($description)) {
                    $updateWhens['description'] = $description->it;
                }
            }

            if ($when->excerpt != null) {
                $excerpt = json_decode($when->excerpt);
                if (isset($excerpt)) {
                    $updateWhens['excerpt'] = $excerpt->it;
                }
            }

            if (count($updateWhens)) {
                DB::table('taxonomy_whens')
                    ->where('id', $when->id)
                    ->update($updateWhens);
            }
        });

        DB::table('taxonomy_targets')->lazyById()->each(function ($target) {
            $updateTargets = [];
            if ($target->name != null) {
                $name = json_decode($target->name);
                if (isset($name)) {
                    $updateTargets['name'] = $name->it;
                }
            }

            if ($target->description != null) {
                $description = json_decode($target->description);
                if (isset($description)) {
                    $updateTargets['description'] = $description->it;
                }
            }

            if ($target->excerpt != null) {
                $excerpt = json_decode($target->excerpt);
                if (isset($excerpt)) {
                    $updateTargets['excerpt'] = $excerpt->it;
                }
            }

            if (count($updateTargets)) {
                DB::table('taxonomy_targets')
                    ->where('id', $target->id)
                    ->update($updateTargets);
            }
        });

        DB::table('taxonomy_themes')->lazyById()->each(function ($theme) {
            $updateThemes = [];
            if ($theme->name != null) {
                $name = json_decode($theme->name);
                if (isset($name)) {
                    $updateThemes['name'] = $name->it;
                }
            }

            if ($theme->description != null) {
                $description = json_decode($theme->description);
                if (isset($description)) {
                    $updateThemes['description'] = $description->it;
                }
            }

            if ($theme->excerpt != null) {
                $excerpt = json_decode($theme->excerpt);
                if (isset($excerpt)) {
                    $updateThemes['excerpt'] = $excerpt->it;
                }
            }

            if (count($updateThemes)) {
                DB::table('taxonomy_themes')
                    ->where('id', $theme->id)
                    ->update($updateThemes);
            }
        });

        DB::table('taxonomy_poi_types')->lazyById()->each(function ($poiType) {
            $updatePoiTypes = [];
            if ($poiType->name != null) {
                $name = json_decode($poiType->name);
                if (isset($name)) {
                    $updatePoiTypes['name'] = $name->it;
                }
            }

            if ($poiType->description != null) {
                $description = json_decode($poiType->description);
                if (isset($description)) {
                    $updatePoiTypes['description'] = $description->it;
                }
            }

            if ($poiType->excerpt != null) {
                $excerpt = json_decode($poiType->excerpt);
                if (isset($excerpt)) {
                    $updatePoiTypes['excerpt'] = $excerpt->it;
                }
            }

            if (count($updatePoiTypes)) {
                DB::table('taxonomy_poi_types')
                    ->where('id', $poiType->id)
                    ->update($updatePoiTypes);
            }
        });
    }
};
