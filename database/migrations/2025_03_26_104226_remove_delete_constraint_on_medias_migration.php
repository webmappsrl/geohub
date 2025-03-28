<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveDeleteConstraintOnMediasMigration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $tablesWithFeatureImage = [
            'ec_pois',
            'ec_tracks',
            'taxonomy_wheres',
            'taxonomy_whens',
            'taxonomy_themes',
            'taxonomy_activities',
            'taxonomy_poi_types',
            'taxonomy_targets',
            'layers',
        ];

        foreach ($tablesWithFeatureImage as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // Rimuovi prima l'eventuale vecchia foreign key
                $table->dropForeign(['feature_image']);

                // Ricrea la foreign key con onDelete('set null')
                $table->foreign('feature_image')
                    ->references('id')
                    ->on('ec_media')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
