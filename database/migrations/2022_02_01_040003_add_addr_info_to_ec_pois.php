<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddrInfoToEcPois extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ec_pois', function (Blueprint $table) {
  
        $table->string('addr_street')->nullable();
        $table->string('addr_housenumber')->nullable();
        $table->string('addr_postcode')->nullable();
        $table->string('addr_locality')->nullable();
        $table->string('opening_hours')->nullable();

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
            $table->dropColumn('addr_street');
            $table->dropColumn('addr_housenumber');
            $table->dropColumn('addr_postcode');
            $table->dropColumn('addr_locality');
            $table->dropColumn('opening_hours');
        });
    }
}
