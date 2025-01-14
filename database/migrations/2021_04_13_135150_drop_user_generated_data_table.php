<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUserGeneratedDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('user_generated_data');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('user_generated_data', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('name')->default('');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('app_id', 100);
            $table->geometry('geometry')->nullable();
            $table->jsonb('raw_data')->nullable();
            $table->jsonb('raw_gallery')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }
}
