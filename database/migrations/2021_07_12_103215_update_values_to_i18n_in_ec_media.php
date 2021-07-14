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
            $update = [];
            if (null != $media->name) {
                $name = json_encode(['it' => $media->name, 'en' => $media->name]);
                $update['name'] = $name;
            }

            if (null != $media->description) {
                $description = json_encode(['it' => $media->description, 'en' => $media->description]);
                $update['description'] = $description;
            }

            if (null != $media->excerpt) {
                $excerpt = json_encode(['it' => $media->excerpt, 'en' => $media->excerpt]);
                $update['excerpt'] = $excerpt;
            }

            if (count($update)) {
                DB::table('ec_media')
                    ->where('id', $media->id)
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
        DB::table('ec_media')->lazyById()->each(function ($media) {
            $update = [];
            if (null != $media->name) {
                $name = json_decode($media->name);
                if (isset($name)) {
                    $update['name'] = $name->it;
                }
            }

            if (null != $media->description) {
                $description = json_decode($media->description);
                if (isset($description)) {
                    $update['description'] = $description->it;
                }
            }

            if (null != $media->excerpt) {
                $excerpt = json_decode($media->excerpt);
                if (isset($excerpt)) {
                    $update['excerpt'] = $excerpt->it;
                }
            }

            if (count($update)) {
                DB::table('ec_media')
                    ->where('id', $media->id)
                    ->update($update);
            }
        });
    }
}
