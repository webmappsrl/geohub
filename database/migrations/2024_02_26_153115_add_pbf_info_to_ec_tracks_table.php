<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPbfInfoToEcTracksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ec_tracks', function (Blueprint $table) {
            $table->json('layers')->nullable();
            $table->json('themes')->nullable();
            $table->json('activities')->nullable();
            $table->json('searchable')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ec_tracks', function (Blueprint $table) {
            $table->dropColumn('layers');
            $table->dropColumn('themes');
            $table->dropColumn('activities');
            $table->dropColumn('searchable');
        });
    }
}
