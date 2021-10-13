<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('sync', function (Blueprint $table) {
            $table->id();
            $table->timestamp('last_update')->default(now());
            $table->timestamp('last_item_date')->default(now());
            $table->string('type');

            $table->unique(['type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('sync');
    }
}
