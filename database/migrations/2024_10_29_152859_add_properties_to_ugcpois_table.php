<?php

use App\Models\App;
use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPropertiesToUgcpoisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ugc_pois', function (Blueprint $table) {
            $table->jsonb('properties')->nullable();
        });
        Schema::table('ugc_media', function (Blueprint $table) {
            $table->jsonb('properties')->nullable();
        });
        Schema::table('ugc_tracks', function (Blueprint $table) {
            $table->jsonb('properties')->nullable();
        });

        // Popola il campo 'properties' con 'name' e 'description' per ogni record esistente
        UgcPoi::all()->each(function ($ugcpoi) {
            $ugcpoi->populateProperties();
            $ugcpoi->populatePropertyForm('poi_acquisition_form');
        });
        UgcMedia::all()->each(function ($ugcmedia) {
            $ugcmedia->populateProperties();
            $ugcmedia->populatePropertyMedia();
        });

        UgcTrack::all()->each(function ($ugctrack) {
            $ugctrack->populateProperties();
            $ugctrack->populatePropertyForm('track_acquisition_form');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ugc_pois', function (Blueprint $table) {
            $table->dropColumn('properties');
        });
        Schema::table('ugc_media', function (Blueprint $table) {
            $table->dropColumn('properties');
        });
        Schema::table('ugc_tracks', function (Blueprint $table) {
            $table->dropColumn('properties');
        });
    }
}
