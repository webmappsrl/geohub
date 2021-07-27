<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsConfigImagesToApps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->string('icon')->nullable();
            $table->string('splash')->nullable();
            $table->string('icon_small')->nullable();
            $table->string('feature_image')->nullable();
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
            $table->dropColumn('icon');
            $table->dropColumn('splash');
            $table->dropColumn('icon_small');
            $table->dropColumn('feature_image');
        });
    }
}
