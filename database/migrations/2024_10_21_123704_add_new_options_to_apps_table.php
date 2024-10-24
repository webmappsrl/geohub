<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewOptionsToAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apps', function (Blueprint $table) {
            $default = [
                'show_duration_forward' => true,
                'show_duration_backward' => true,
                'show_distance' => true,
                'show_ascent' => true,
                'show_descent' => true,
                'show_ele_max' => true,
                'show_ele_min' => true,
                'show_ele_from' => true,
                'show_ele_to' => true,
            ];

            $table->jsonb('track_technical_details')->nullable()->default(json_encode($default));
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
            $table->dropColumn('track_technical_details');
        });
    }
}
