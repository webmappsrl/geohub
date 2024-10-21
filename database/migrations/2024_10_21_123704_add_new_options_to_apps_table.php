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
            $columns = [
                'table_details_show_duration_forward' => true,
                'table_details_show_duration_backward' => true,
                'table_details_show_distance' => true,
                'table_details_show_ascent' => true,
                'table_details_show_descent' => true,
                'table_details_show_ele_max' => true,
                'table_details_show_ele_min' => true,
                'table_details_show_ele_from' => true,
                'table_details_show_ele_to' => true,
            ];

            foreach ($columns as $column => $default) {
                if (!Schema::hasColumn('apps', $column)) {
                    $table->boolean($column)->default($default);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
