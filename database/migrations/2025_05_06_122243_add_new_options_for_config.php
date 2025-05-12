<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewOptionsForConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->boolean('show_travel_mode')->default(false);
            $table->boolean('show_get_directions')->default(false);
            $table->boolean('show_media_name')->default(false);
            $table->boolean('show_features_in_viewport')->default(false);
            $table->boolean('show_embedded_html')->default(false);
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
            $table->dropColumn('show_travel_mode');
            $table->dropColumn('show_get_directions');
            $table->dropColumn('show_media_name');
            $table->dropColumn('show_features_in_viewport');
            $table->dropColumn('show_embedded_html');
        });
    }
}
