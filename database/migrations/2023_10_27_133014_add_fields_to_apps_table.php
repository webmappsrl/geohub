<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->text('short_description')->nullable();
            $table->text('long_description')->nullable();
            $table->string('privacy_url')->nullable();
            $table->string('website_url')->nullable();
            $table->json('keywords')->nullable();
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
            $table->dropColumn([
                'short_description',
                'long_description',
                'privacy_url',
                'website_url',
                'keywords',
            ]);
        });
    }
}
