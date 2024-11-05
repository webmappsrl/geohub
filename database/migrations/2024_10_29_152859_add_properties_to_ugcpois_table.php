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
            $properties = $ugcpoi->properties;
            // Trova l'applicazione associata e ottieni lo schema `poi_acquisition_form`
            if (is_numeric($ugcpoi->app_id)) {

                $app = App::where('id', $ugcpoi->app_id)->first();
            } else {
                $sku = $ugcpoi->app_id;
                if ($sku === 'it.net7.parcoforestecasentinesi') {
                    $sku = 'it.netseven.forestecasentinesi';
                }
                $app = App::where('sku', $ugcpoi->app_id)->first();
            }
            if ($app && $app->poi_acquisition_form) {
                $formSchema = json_decode($app->poi_acquisition_form, true);
                // Trova lo schema corretto basato sull'ID, se esiste in `raw_data`
                if (isset($properties['id'])) {
                    $currentSchema = collect($formSchema)->firstWhere('id', $properties['id']);

                    if ($currentSchema) {
                        // Rimuove i campi del form da `properties` e li aggiunge sotto la chiave `form`
                        $formFields = [];
                        if (isset($properties['id'])) {
                            $formFields['id'] = $properties['id'];
                            unset($properties['id']); // Rimuovi `id` da `properties`
                        }
                        foreach ($currentSchema['fields'] as $field) {
                            $label = $field['name'] ?? 'unknown';
                            if (isset($properties[$label])) {
                                $formFields[$label] = $properties[$label];
                                unset($properties[$label]); // Rimuove il campo da `properties`
                            }
                        }

                        $properties['form'] = $formFields; // Aggiunge i campi del form sotto `form`
                    }
                }
            }
        });
        UgcMedia::all()->each(function ($ugcmedia) {
            $ugcmedia->populateProperties();
        });

        UgcTrack::all()->each(function ($ugctrack) {
            $ugctrack->populateProperties();
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
