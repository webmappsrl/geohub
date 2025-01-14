<?php

use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            $properties = $ugctrack->properties;
            $locations = $properties['locations'] ?? [];

            // Verifica che ci siano location valide
            if (! empty($locations)) {
                // Verifica che ci siano location valide con altitudine
                $coordinates = array_filter(array_map(function ($location) use ($ugctrack) {
                    if (isset($location['longitude'], $location['latitude'], $location['altitude'])) {
                        return "{$location['longitude']} {$location['latitude']} {$location['altitude']}";
                    } else {
                        Log::warning("Manca l'altitudine per una location nella traccia con ID {$ugctrack->id}");

                        return null;
                    }
                }, $locations));
                if (! empty($coordinates) && count($coordinates) > 1) {
                    // Crea una stringa WKT per la geometria 3D (LINESTRING o MULTIPOINT)
                    $wkt = 'LINESTRING Z('.implode(', ', $coordinates).')';

                    // Salva la geometria 3D utilizzando ST_GeomFromText
                    $ugctrack->geometry = DB::raw("ST_GeomFromText('$wkt', 4326)");
                    $ugctrack->saveQuietly();
                }
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
