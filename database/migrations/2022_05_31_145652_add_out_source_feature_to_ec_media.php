<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOutSourceFeatureToEcMedia extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ec_media', function (Blueprint $table) {
            $table->unsignedBigInteger('out_source_feature_id')->nullable();
            $table->foreign('out_source_feature_id')
                ->references('id')
                ->on('out_source_features')
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
        Schema::table('ec_media', function (Blueprint $table) {
            $table->dropColumn('out_source_feature_id');
        });
    }
}
