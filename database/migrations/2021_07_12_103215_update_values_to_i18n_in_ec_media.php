<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateValuesToI18nInEcMedia extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('ec_media')->lazyById()->each(function ($media) {
            $name = json_encode(['it' => $media->name, 'en' => $media->name]);
            $description = json_encode(['it' => $media->description, 'en' => $media->description]);
            DB::table('ec_media')
                ->where('id', $media->id)
                ->update([
                    'name' => $name,
                    'description' => $description,
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
        DB::table('ec_media')->lazyById()->each(function ($media) {
            $name = json_decode($media->name);
            $description = json_decode($media->description);
            DB::table('ec_media')
                ->where('id', $media->id)
                ->update([
                    'name' => $name->it,
                    'description' => $description->it,
                ]);
        });
    }
}
