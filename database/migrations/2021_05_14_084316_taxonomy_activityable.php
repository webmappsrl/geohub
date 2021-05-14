<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TaxonomyActivityable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxonomy_activityables', function (Blueprint $table) {
            $table->integer('taxonomy_activityable_id')->unsigned();
            $table->integer('taxonomy_activity_id')->unsigned();
            $table->string('taxonomy_activityable_type');
            $table->foreign('taxonomy_activity_id')
                ->references('id')
                ->on('taxonomy_activities');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('taxonomy_activityables');
    }
}
