<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUgcTracksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ugc_tracks', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('name')->default('');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('app_id', 100);
            $table->lineString('geometry')->nullable();
            $table->jsonb('raw_data')->nullable();
            $table->jsonb('raw_gallery')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });

        MigrationsHelpers::addDefaultPermissions('ugc_tracks');

        $currentUGCs = DB::table('user_generated_data')
            ->select(
                'id',
                DB::raw('ST_GeometryType(St_GeometryFromText(ST_AsText(geometry))) as geom')
            )
            ->get();

        foreach ($currentUGCs as $currentUgc) {
            if (strtolower($currentUgc->geom) === 'st_linestring') {
                $ugc = DB::table('user_generated_data')->find($currentUgc->id);
                DB::table('ugc_tracks')->insert([
                    'created_at' => $ugc->created_at,
                    'updated_at' => $ugc->updated_at,
                    'app_id' => $ugc->app_id,
                    'geometry' => $ugc->geometry,
                    'raw_data' => $ugc->raw_data,
                    'raw_gallery' => $ugc->raw_gallery,
                    'user_id' => $ugc->user_id,
                    'name' => $ugc->name,
                ]);
                DB::table('user_generated_data')->delete($ugc->id);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        MigrationsHelpers::removeDefaultPermissions('ugc_tracks');

        $currentUGCs = DB::table('ugc_tracks')->get();

        foreach ($currentUGCs as $currentUgc) {
            $ugc = DB::table('ugc_tracks')->find($currentUgc->id);
            DB::table('user_generated_data')->insert([
                'created_at' => $ugc->created_at,
                'updated_at' => $ugc->updated_at,
                'app_id' => $ugc->app_id,
                'geometry' => $ugc->geometry,
                'raw_data' => $ugc->raw_data,
                'raw_gallery' => $ugc->raw_gallery,
                'user_id' => $ugc->user_id,
            ]);
            DB::table('ugc_tracks')->delete($ugc->id);
        }

        Schema::dropIfExists('ugc_tracks');
    }
}
