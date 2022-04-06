<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class OutSourceOSMProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(OutSourceOSMProvider::class, function ($app) {
            return new OutSourceOSMProvider($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public function getItem(string $id): array {
        $data = [];

        $db = DB::connection('out_source_osm');
        $item = $db->table('hiking_routes')
            ->where('relation_id',$id)
            ->select([
                'ref',
                'name',
                'cai_scale',
                'from',
                'to',
            ])
            ->first();
        if(!is_null($item)) {
            // ADD Geometry
            $geometry = null;
            $res = $db->select(DB::raw('select st_asgeojson(ST_transform(geom,4326)) as geometry from hiking_routes where relation_id='.$id));
            if(isset($res[0]->geometry)) {
                $geometry=$res[0]->geometry;
            }

            // NAME
            if(!empty($item->name)) {
                $name = $item->name;
            } else if(!empty($item->ref)) {
                $name = "Percorso escursionistico {$item->ref}";
            } else {
                $name = "Percorso escursionistico (OSMID:$id)";
            }

            $data=[
                'provider' => get_class($this),
                'source_id'=> $id,
                'tags' => [
                    'name' => ['it' => $name],
                    'ref' => $item->ref,
                    'cai_scale' => $item->cai_scale,
                    'from' => $item->from,
                    'to' => $item->to,
                ],
                'geometry' => $geometry,
            ];
        }
        return $data;
    }
}