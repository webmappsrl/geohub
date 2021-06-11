<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContactFieldsToEcPois extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ec_pois', function (Blueprint $table) {
            $table->text('contact_phone')->nullable();
            $table->text('contact_email')->nullable();
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
            $table->dropColumn('contact_phone');
            $table->dropColumn('contact_email');
        });
    }
}
