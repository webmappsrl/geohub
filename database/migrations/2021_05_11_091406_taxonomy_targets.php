<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TaxonomyTargets extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('taxonomy_targets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('user_id')->unsigned();
            $table->text('name');
            $table->text('description')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('import_method')->nullable();
            $table->string('source_id')->nullable();
            $table->text('source')->nullable();

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });

        MigrationsHelpers::addDefaultPermissions('taxonomy_targets');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('taxonomy_targets');
    }
}
