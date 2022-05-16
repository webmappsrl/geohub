<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalInfoToEcPois extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ec_pois', function (Blueprint $table) {
            $table->string('capacity')->nullable();
            $table->string('stars')->nullable();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->string('code')->nullable();

            $table->boolean('noDetails')->nullable();
            $table->boolean('noInteraction')->nullable();

            $table->timestamp('accessibility_validity_date')->nullable();
            $table->string('accessibility_pdf')->nullable();
            
            $table->boolean('access_mobility_check')->nullable();
            $table->string('access_mobility_level')->nullable();
            $table->text('access_mobility_description')->nullable();
            
            $table->boolean('access_hearing_check')->nullable();
            $table->string('access_hearing_level')->nullable();
            $table->text('access_hearing_description')->nullable();
            
            $table->boolean('access_vision_check')->nullable();
            $table->string('access_vision_level')->nullable();
            $table->text('access_vision_description')->nullable();
            
            $table->boolean('access_cognitive_check')->nullable();
            $table->string('access_cognitive_level')->nullable();
            $table->text('access_cognitive_description')->nullable();
            
            $table->boolean('access_food_check')->nullable();
            $table->text('access_food_description')->nullable();
            
            $table->boolean('reachability_by_bike_check')->nullable();
            $table->text('reachability_by_bike_description')->nullable();
            
            $table->boolean('reachability_on_foot_check')->nullable();
            $table->text('reachability_on_foot_description')->nullable();
            
            $table->boolean('reachability_by_car_check')->nullable();
            $table->text('reachability_by_car_description')->nullable();
            
            $table->boolean('reachability_by_public_transportation_check')->nullable();
            $table->text('reachability_by_public_transportation_description')->nullable();
            
            $table->integer('zindex')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ec_pois', function (Blueprint $table) {
            $table->dropColumn('');
            $table->dropColumn('capacity');
            $table->dropColumn('stars');
            $table->dropColumn('color');
            $table->dropColumn('icon');
            $table->dropColumn('code');

            $table->dropColumn('noDetails');
            $table->dropColumn('noInteraction');

            $table->dropColumn('accessibility_validity_date');
            $table->dropColumn('accessibility_pdf');
            
            $table->dropColumn('access_mobility_check');
            $table->dropColumn('access_mobility_level');
            $table->dropColumn('access_mobility_description');
            
            $table->dropColumn('access_hearing_check');
            $table->dropColumn('access_hearing_level');
            $table->dropColumn('access_hearing_description');
            
            $table->dropColumn('access_vision_check');
            $table->dropColumn('access_vision_level');
            $table->dropColumn('access_vision_description');
            
            $table->dropColumn('access_cognitive_check');
            $table->dropColumn('access_cognitive_level');
            $table->dropColumn('access_cognitive_description');
            
            $table->dropColumn('access_food_check');
            $table->dropColumn('access_food_description');
            
            $table->dropColumn('reachability_by_bike_check');
            $table->dropColumn('reachability_by_bike_description');
            
            $table->dropColumn('reachability_on_foot_check');
            $table->dropColumn('reachability_on_foot_description');
            
            $table->dropColumn('reachability_by_car_check');
            $table->dropColumn('reachability_by_car_description');
            
            $table->dropColumn('reachability_by_public_transportation_check');
            $table->dropColumn('reachability_by_public_transportation_description');
            
            $table->dropColumn('zindex');
        });
    }
}
