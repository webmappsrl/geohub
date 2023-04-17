<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMetadataToUgcTracks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ugc_tracks', function (Blueprint $table) {
            $table->text('metadata')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ugc_tracks', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
}
