<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->boolean('filter_activity')->nullable();
            $table->string('filter_activity_exclude')->nullable();
            $table->boolean('filter_poi_type')->nullable();
            $table->string('filter_poi_type_exclude')->nullable();
            $table->boolean('filter_track_duration')->nullable();
            $table->boolean('filter_track_distance')->nullable();
            $table->integer('filter_track_duration_steps')->nullable();
            $table->integer('filter_track_duration_min')->nullable();
            $table->integer('filter_track_duration_max')->nullable();
            $table->boolean('filter_track_difficulty')->nullable();
            $table->integer('filter_track_distance_steps')->nullable();
            $table->integer('filter_track_distance_min')->nullable();
            $table->integer('filter_track_distance_max')->nullable();
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
            $table->dropColumn([
                'filter_activity',
                'filter_activity_exclude',
                'filter_poi_type',
                'filter_poi_type_exclude',
                'filter_track_duration',
                'filter_track_distance',
                'filter_track_duration_steps',
                'filter_track_duration_min',
                'filter_track_duration_max',
                'filter_track_distance_steps',
                'filter_track_distance_min',
                'filter_track_distance_max',
                'filter_track_difficulty',
            ]);
        });
    }
};
