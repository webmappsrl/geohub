<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // APP section (config.json)
            $table->string('name');
            $table->string('app_id')->unique();
            $table->string('customerName');

            // MAP section (zoom)
            $table->integer('maxZoom')->default(16);
            $table->integer('minZoom')->default(12);
            $table->integer('defZoom')->default(12);

            // THEME section
            $table->string('fontFamilyHeader')->default('Roboto Slab');
            $table->string('fontFamilyContent')->default('Roboto');
            $table->string('defaultFeatureColor')->default('#de1b0d');
            $table->string('primary')->default('#de1b0d');

            // OPTIONS section
            $table->string('startUrl')->default('/main/explore');
            $table->boolean('showEditLink')->default(false);
            $table->boolean('skipRouteIndexDownload')->default(true);
            $table->float('poiMinRadius')->default(0.5);
            $table->float('poiMaxRadius')->default(1.2);
            $table->float('poiIconZoom')->default(16);
            $table->float('poiIconRadius')->default(1);
            $table->float('poiMinZoom')->default(13);
            $table->float('poiLabelMinZoom')->default(10.5);
            $table->boolean('showTrackRefLabel')->default(false);

            // TABLE section
            $table->boolean('showGpxDownload')->default(false);
            $table->boolean('showKmlDownload')->default(false);
            $table->boolean('showRelatedPoi')->default(false);

            // ROUTING
            $table->boolean('enableRouting')->default(false);

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign("user_id")
                ->references("id")
                ->on("users");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('apps');
    }
}
