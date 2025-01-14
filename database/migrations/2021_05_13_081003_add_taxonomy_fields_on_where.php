<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaxonomyFieldsOnWhere extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('taxonomy_wheres', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->text('excerpt')->nullable();
            $table->text('source')->nullable();
            $table->integer('user_id')->unsigned()->nullable();

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('taxonomy_wheres', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('excerpt');
            $table->dropColumn('source');
            $table->dropColumn('user_id');
        });
    }
}
