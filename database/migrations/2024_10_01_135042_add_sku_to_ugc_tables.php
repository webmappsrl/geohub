<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSkuToUgcTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ugc_tracks', function (Blueprint $table) {
            $table->string('sku')->nullable();
        });
        Schema::table('ugc_media', function (Blueprint $table) {
            $table->string('sku')->nullable();
        });
        Schema::table('ugc_pois', function (Blueprint $table) {
            $table->string('sku')->nullable();
        });

        // Popolare la colonna SKU per ugc_tracks
        $this->updateTableWithSku('ugc_tracks');

        // Popolare la colonna SKU per ugc_media
        $this->updateTableWithSku('ugc_media');

        // Popolare la colonna SKU per ugc_pois
        $this->updateTableWithSku('ugc_pois');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Rimuovi la colonna sku da tutte e tre le tabelle
        Schema::table('ugc_tracks', function (Blueprint $table) {
            $table->dropColumn('sku');
        });
        Schema::table('ugc_media', function (Blueprint $table) {
            $table->dropColumn('sku');
        });
        Schema::table('ugc_pois', function (Blueprint $table) {
            $table->dropColumn('sku');
        });
    }

    /**
     * Funzione helper per aggiornare la colonna SKU in una tabella specifica
     *
     * @return void
     */
    protected function updateTableWithSku(string $tableName)
    {
        DB::table($tableName)->orderBy('id')->chunk(100, function ($records) use ($tableName) {
            foreach ($records as $record) {
                if (is_numeric($record->app_id)) {
                    // Se `app_id` è numerico, prendi l'`app_id` dalla tabella `apps` come SKU
                    $app = DB::table('apps')->where('id', $record->app_id)->first();
                    if ($app && isset($app->app_id)) {
                        DB::table($tableName)
                            ->where('id', $record->id)
                            ->update(['sku' => $app->app_id]);
                    }
                } else {
                    // Se `app_id` è uno SKU, usa quel valore come SKU e aggiorna `app_id` con l'ID dell'app
                    $app = DB::table('apps')->where('app_id', $record->app_id)->first();
                    if ($app && isset($app->id)) {
                        DB::table($tableName)
                            ->where('id', $record->id)
                            ->update(['sku' => $record->app_id, 'app_id' => $app->id]);
                    }
                }
            }
        });
    }
}
