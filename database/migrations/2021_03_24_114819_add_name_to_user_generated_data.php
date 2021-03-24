<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNameToUserGeneratedData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_generated_data', function (Blueprint $table) {
            $table->text('name')->default('');
        });

        $data = \App\Models\UserGeneratedData::get();
        foreach ($data as $row) {
            $json = json_decode($row->raw_data, true);
            $name = isset($json['name']) ? $json['name'] : '';
            unset($json['name']);
            $row->name = $name;
            $row->raw_data = $json;
            $row->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $data = \App\Models\UserGeneratedData::get();
        foreach ($data as $row) {
            $json = json_decode($row->raw_data, true);
            $json['name'] = $row->name;
            $row->raw_data = json_encode($json);
            $row->save();
        }

        Schema::table('user_generated_data', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
}
