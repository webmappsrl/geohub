<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFilterLabelsToAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->text('filter_activity_label')->nullable()->default('{"it":"Attività","en":"Activity"}');
            $table->text('filter_theme_label')->nullable()->default('{"it":"Tema","en":"Theme"}');
            $table->text('filter_poi_type_label')->nullable()->default('{"it":"Punti di interesse","en":"Points of interest"}');
            $table->text('filter_track_duration_label')->nullable()->default('{"it":"Tempo di percorrenza","en":"Travel time"}');
            $table->text('filter_track_distance_label')->nullable()->default('{"it":"Lunghezza del sentiero","en":"Path length"}');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn(['filter_activity_label', 'filter_theme_label', 'filter_poi_type_label', 'filter_track_duration_label', 'filter_track_distance_label']);
        });
    }
}
