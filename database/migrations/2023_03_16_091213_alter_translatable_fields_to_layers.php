<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterTranslatableFieldsToLayers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('layers')->lazyById()->each(function ($layer) {
            $update = [];
            if (null != $layer->title) {
                $title = json_encode(['it' => $layer->title, 'en' => $layer->title]);
                $update['title'] = $title;
            }
            if (null != $layer->subtitle) {
                $subtitle = json_encode(['it' => $layer->subtitle, 'en' => $layer->subtitle]);
                $update['subtitle'] = $subtitle;
            }
            if (null != $layer->description) {
                $description = json_encode(['it' => $layer->description, 'en' => $layer->description]);
                $update['description'] = $description;
            }

            if (count($update)) {
                DB::table('layers')
                    ->where('id', $layer->id)
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
        DB::table('layers')->lazyById()->each(function ($layer) {
            $update = [];
            if (null != $layer->title) {
                $title = json_decode($layer->title);
                if (isset($title)) {
                    $update['title'] = $title->it;
                }
            }
            if (null != $layer->subtitle) {
                $subtitle = json_decode($layer->subtitle);
                if (isset($subtitle)) {
                    $update['subtitle'] = $subtitle->it;
                }
            }
            if (null != $layer->description) {
                $description = json_decode($layer->description);
                if (isset($description)) {
                    $update['description'] = $description->it;
                }
            }
            if (count($update)) {
                DB::table('layers')
                    ->where('id', $layer->id)
                    ->update($update);
            }
        });
    }
}
