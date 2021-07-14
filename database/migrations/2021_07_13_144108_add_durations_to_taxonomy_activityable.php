<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDurationsToTaxonomyActivityable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('taxonomy_activityables', function (Blueprint $table) {
            $table->integer('duration_forward')->nullable()->default(0);
            $table->integer('duration_backward')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('taxonomy_activityables', function (Blueprint $table) {
            $table->dropColumn('duration_forward');
            $table->dropColumn('duration_backward');
        });
    }
}
