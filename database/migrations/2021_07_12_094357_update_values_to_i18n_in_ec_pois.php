<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateValuesToI18nInEcPois extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('ec_pois')->lazyById()->each(function ($poi) {
            $update = [];
            if ($poi->name != null) {
                $name = json_encode(['it' => $poi->name, 'en' => $poi->name]);
                $update['name'] = $name;
            }

            if ($poi->description != null) {
                $description = json_encode(['it' => $poi->description, 'en' => $poi->description]);
                $update['description'] = $description;
            }

            if ($poi->excerpt != null) {
                $excerpt = json_encode(['it' => $poi->excerpt, 'en' => $poi->excerpt]);
                $update['excerpt'] = $excerpt;
            }

            if (count($update)) {
                DB::table('ec_pois')
                    ->where('id', $poi->id)
                    ->update($update);
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
        DB::table('ec_pois')->lazyById()->each(function ($poi) {
            $update = [];
            if ($poi->name != null) {
                $name = json_decode($poi->name);
                if (isset($name)) {
                    $update['name'] = $name->it;
                }
            }

            if ($poi->description != null) {
                $description = json_decode($poi->description);
                if (isset($description)) {
                    $update['description'] = $description->it;
                }
            }

            if ($poi->excerpt != null) {
                $excerpt = json_decode($poi->excerpt);
                if (isset($excerpt)) {
                    $update['excerpt'] = $excerpt->it;
                }
            }

            if (count($update)) {
                DB::table('ec_pois')
                    ->where('id', $poi->id)
                    ->update($update);
            }
        });
    }
}
