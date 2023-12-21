<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('taxonomy_wheres', function (Blueprint $table) {
            $table->integer('feature_image')->nullable()->unsigned();
            $table->foreign('feature_image')
                ->references('id')
                ->on('ec_media')
                ->onDelete('cascade');
        });
        Schema::table('taxonomy_whens', function (Blueprint $table) {
            $table->integer('feature_image')->nullable()->unsigned();
            $table->foreign('feature_image')
                ->references('id')
                ->on('ec_media')
                ->onDelete('cascade');
        });
        Schema::table('taxonomy_themes', function (Blueprint $table) {
            $table->integer('feature_image')->nullable()->unsigned();
            $table->foreign('feature_image')
                ->references('id')
                ->on('ec_media')
                ->onDelete('cascade');
        });
        Schema::table('taxonomy_activities', function (Blueprint $table) {
            $table->integer('feature_image')->nullable()->unsigned();
            $table->foreign('feature_image')
                ->references('id')
                ->on('ec_media')
                ->onDelete('cascade');
        });
        Schema::table('taxonomy_poi_types', function (Blueprint $table) {
            $table->integer('feature_image')->nullable()->unsigned();
            $table->foreign('feature_image')
                ->references('id')
                ->on('ec_media')
                ->onDelete('cascade');
        });
        Schema::table('taxonomy_targets', function (Blueprint $table) {
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
        Schema::table('taxonomy_wheres', function (Blueprint $table) {
            $table->dropColumn('feature_image')->nullable();
        });
        Schema::table('taxonomy_whens', function (Blueprint $table) {
            $table->dropColumn('feature_image')->nullable();
        });
        Schema::table('taxonomy_themes', function (Blueprint $table) {
            $table->dropColumn('feature_image')->nullable();
        });
        Schema::table('taxonomy_activities', function (Blueprint $table) {
            $table->dropColumn('feature_image')->nullable();
        });
        Schema::table('taxonomy_poi_types', function (Blueprint $table) {
            $table->dropColumn('feature_image')->nullable();
        });
        Schema::table('taxonomy_activities', function (Blueprint $table) {
            $table->dropColumn('feature_image')->nullable();
        });
        Schema::table('taxonomy_targets', function (Blueprint $table) {
            $table->dropColumn('feature_image')->nullable();
        });
    }
};
