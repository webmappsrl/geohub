<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnershipUserTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('partnership_user', function (Blueprint $table) {
            $table->integer('partnership_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->foreign('partnership_id')
                ->references('id')
                ->on('partnerships');
            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->unique('partnership_id', 'user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('partnership_user');
    }
}
