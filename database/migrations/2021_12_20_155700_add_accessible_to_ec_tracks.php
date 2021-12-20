<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccessibleToEcTracks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ec_tracks', function (Blueprint $table) {
            $table->boolean('not_accessible')->default(false);
            $table->text('not_accessible_message')->nullable();
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
            $table->dropColumn('not_accessible');
            $table->dropColumn('not_accessible_message');
        });
    }
}
