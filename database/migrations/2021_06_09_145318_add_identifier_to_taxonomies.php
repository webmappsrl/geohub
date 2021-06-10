<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdentifierToTaxonomies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('taxonomy_activities', function (Blueprint $table) {
            $table->text('identifier')->unique()->nullable();
        });
        Schema::table('taxonomy_poi_types', function (Blueprint $table) {
            $table->text('identifier')->unique()->nullable();
        });
        Schema::table('taxonomy_targets', function (Blueprint $table) {
            $table->text('identifier')->unique()->nullable();
        });
        Schema::table('taxonomy_themes', function (Blueprint $table) {
            $table->text('identifier')->unique()->nullable();
        });
        Schema::table('taxonomy_whens', function (Blueprint $table) {
            $table->text('identifier')->unique()->nullable();
        });
        Schema::table('taxonomy_wheres', function (Blueprint $table) {
            $table->text('identifier')->unique()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('taxonomy_activities', function (Blueprint $table) {
            $table->dropColumn('identifier');
        });
        Schema::table('taxonomy_poi_types', function (Blueprint $table) {
            $table->dropColumn('identifier');
        });
        Schema::table('taxonomy_targets', function (Blueprint $table) {
            $table->dropColumn('identifier');
        });
        Schema::table('taxonomy_themes', function (Blueprint $table) {
            $table->dropColumn('identifier');
        });
        Schema::table('taxonomy_whens', function (Blueprint $table) {
            $table->dropColumn('identifier');
        });
        Schema::table('taxonomy_wheres', function (Blueprint $table) {
            $table->dropColumn('identifier');
        });
    }
}
