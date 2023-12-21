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
        Schema::table('apps', function (Blueprint $table) {
            $table->boolean('flow_line_quote_show')->default(false);
            $table->integer('flow_line_quote_orange')->default(800);
            $table->integer('flow_line_quote_red')->default(1500);
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
            $table->dropColumn('flow_line_quote_show');
            $table->dropColumn('flow_line_quote_orange');
            $table->dropColumn('flow_line_quote_red');
        });
    }
};
