<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('user_generated_data', function (Blueprint $table) {
            $table->text('name')->default('');
        });

        $data = DB::table('user_generated_data')->get();
        foreach ($data as $row) {
            $json = json_decode($row->raw_data, true);
            $name = isset($json['name'])
                ? $json['name']
                : (isset($json['title'])
                    ? $json['title']
                    : '');
            if (isset($json['name'])) {
                unset($json['name']);
            } elseif (isset($json['title'])) {
                unset($json['title']);
            }
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
        $data = UserGeneratedData::get();
        foreach ($data as $row) {
            if (isset($row->name) && ! empty($row->name)) {
                $json = json_decode($row->raw_data, true);
                $json['name'] = $row->name;
                $row->raw_data = json_encode($json);
                $row->save();
            }
        }

        Schema::table('user_generated_data', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
