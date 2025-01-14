<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUgcMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ugc_media', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('app_id', 100);
            $table->point('geometry')->nullable();
            $table->string('relative_url');
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });

        MigrationsHelpers::addDefaultPermissions('ugc_media');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        MigrationsHelpers::removeDefaultPermissions('ugc_media');
        Schema::dropIfExists('ugc_media');
    }
}
