<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColorToOverlayLayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('overlay_layers', function (Blueprint $table) {
            $table->string('stroke_color')->nullable();
            $table->integer('stroke_width')->nullable()->default(2);
            $table->string('fill_color')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('overlay_layers', function (Blueprint $table) {
            $table->dropColumn('stroke_color');
            $table->dropColumn('stroke_width');
            $table->dropColumn('fill_color');
        });
    }
}
