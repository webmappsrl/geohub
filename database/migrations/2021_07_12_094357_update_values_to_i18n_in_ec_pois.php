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
            $name = json_encode(['it' => $poi->name, 'en' => $poi->name]);
            $description = json_encode(['it' => $poi->description, 'en' => $poi->description]);
            $excerpt = json_encode(['it' => $poi->excerpt, 'en' => $poi->excerpt]);
            DB::table('ec_pois')
                ->where('id', $poi->id)
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
        DB::table('ec_pois')->lazyById()->each(function ($poi) {
            $name = json_decode($poi->name);
            $description = json_decode($poi->description);
            $excerpt = json_decode($poi->excerpt);
            DB::table('ec_pois')
                ->where('id', $poi->id)
                ->update([
                    'name' => $name->it,
                    'description' => $description->it,
                    'excerpt' => $excerpt->it,
                ]);
        });
    }
}
