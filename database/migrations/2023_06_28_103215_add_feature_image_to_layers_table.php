<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFeatureImageToLayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('layers', function (Blueprint $table) {
            $table->integer('feature_image')->nullable()->unsigned();
            $table->foreign('feature_image')
                ->references('id')
                ->on('ec_media')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('layers', function (Blueprint $table) {
            $table->dropColumn('feature_image');
        });
    }
}
