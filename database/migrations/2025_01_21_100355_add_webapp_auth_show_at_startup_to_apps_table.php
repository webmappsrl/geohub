<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebappAuthShowAtStartupToAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->boolean('webapp_auth_show_at_startup')->default(false);
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
            $table->dropColumn('webapp_auth_show_at_startup');
        });
    }
}
