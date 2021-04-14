<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ImportMediaFromUGC extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        foreach (['ugc_track', 'ugc_poi'] as $tableName) {
            $associations = $this->importMediaFromTable($tableName . 's');
            $this->updateSchema($tableName);
            $this->insertAssociations($tableName, $associations);
        }
    }

    private function importMediaFromTable(string $tableName): array {
        $result = [];
        $baseImageName = 'media/images/ugc/image_';
        $collection = DB::table($tableName)->get();
        foreach ($collection as $row) {
            if (isset($row->raw_gallery)) {
                $gallery = json_decode($row->raw_gallery, true);
                foreach ($gallery as $imageRaw) {
                    $maxId = DB::table('ugc_media')->max('id');
                    if (is_null($maxId)) $maxId = 0;
                    $maxId++;
                    preg_match("/data:image\/(.*?);/", $imageRaw, $imageExtension);
                    $image = preg_replace('/data:image\/(.*?);base64,/', '', $imageRaw); // remove the type part
                    $image = str_replace(' ', '+', $image);
                    while (Storage::disk('public')->exists(
                        $baseImageName . $maxId . '.' . $imageExtension[1]
                    )) {
                        $maxId++;
                    }

                    $imageName = $baseImageName . $maxId . '.' . $imageExtension[1];
                    Storage::disk('public')->put(
                        $imageName,
                        base64_decode($image)
                    );

                    $geometry = DB::table($tableName)
                        ->select(
                            DB::raw('ST_GeometryType(St_GeometryFromText(ST_AsText(geometry))) as geom')
                        )
                        ->find($row->id);

                    $id = DB::table('ugc_media')->insertGetId([
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                        'user_id' => $row->user_id,
                        'app_id' => $row->app_id,
                        'relative_url' => $imageName,
                        'geometry' => strtolower($geometry->geom) === 'st_point' ? $row->geometry : null
                    ]);

                    if (!isset($result[$row->id])) $result[$row->id] = [];
                    $result[$row->id][] = $id;
                }
            }
        }

        return $result;
    }

    private function updateSchema(string $tableName) {
        Schema::dropColumns($tableName . 's', ['raw_gallery']);
        Schema::create('ugc_media_' . $tableName, function (Blueprint $table) use ($tableName) {
            $table->id();
            $table->unsignedBigInteger('ugc_media_id');
            $table->foreign('ugc_media_id')
                ->references('id')
                ->on('ugc_media');
            $table->unsignedBigInteger($tableName . '_id');
            $table->foreign($tableName . '_id')
                ->references('id')
                ->on($tableName . 's');
        });
    }

    private function insertAssociations(string $tableName, array $associations) {
        foreach ($associations as $tableId => $mediaIds) {
            foreach ($mediaIds as $mediaId) {
                DB::table('ugc_media_' . $tableName)->insert([
                    'ugc_media_id' => $mediaId,
                    $tableName . '_id' => $tableId
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('ugc_pois', function ($table) {
            $table->jsonb('raw_gallery')->nullable();
        });
        Schema::table('ugc_tracks', function ($table) {
            $table->jsonb('raw_gallery')->nullable();
        });
        Schema::dropIfExists('ugc_media_ugc_track');
        Schema::dropIfExists('ugc_media_ugc_poi');
        DB::table('ugc_media')->truncate();
    }
}
