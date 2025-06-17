<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInfoAndEmbeddedElementToEcPois extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // TODO: ADD2WMPACKAGE
        Schema::table('ec_pois', function (Blueprint $table) {
            $table->text('info')->nullable();
            $table->text('embedded_html')->nullable();
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
            $table->dropColumn('info');
            $table->dropColumn('embedded_html');
        });
    }
}
