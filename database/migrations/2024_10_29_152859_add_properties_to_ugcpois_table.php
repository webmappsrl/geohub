<?php

use App\Models\App;
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

        // Popola il campo 'properties' con 'name' e 'description' per ogni record esistente
        DB::table('ugc_pois')->get()->each(function ($ugcpoi) {
            $properties = [
                'name' => $ugcpoi->name,
                'description' => $ugcpoi->description,
            ];

            if (!empty($ugcpoi->raw_data)) {
                $properties = array_merge($properties, (array) json_decode($ugcpoi->raw_data, true));
            }
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

            DB::table('ugc_pois')
                ->where('id', $ugcpoi->id)
                ->update([
                    'properties' => json_encode($properties),
                ]);
        });

        DB::table('ugc_media')->get()->each(function ($ugcmedia) {
            $properties = [];
            if (isset($ugcmedia->name)) {
                $properties['name'] = $ugcmedia->name;
            }
            if (isset($ugcmedia->description)) {
                $properties['description'] = $ugcmedia->description;
            }

            if (!empty($ugcmedia->raw_data)) {
                $properties = array_merge($properties, (array) json_decode($ugcmedia->raw_data, true));
            }

            DB::table('ugc_media')
                ->where('id', $ugcmedia->id)
                ->update([
                    'properties' => json_encode($properties),
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
        Schema::table('ugc_pois', function (Blueprint $table) {
            $table->dropColumn('properties');
        });
    }
}
