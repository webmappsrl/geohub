<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

/**
 * TABLE:
 *      Column      |              Type               | Collation | Nullable |                         Default
------------------+---------------------------------+-----------+----------+----------------------------------------------------------
id_2             | integer                         |           | not null | nextval('sentiero_italia."SI_Tappe_id_2_seq"'::regclass)
geom             | geometry(MultiLineString,32632) |           |          |
id_1             | integer                         |           |          |
id_0             | bigint                          |           |          |
id               | character varying(254)          |           |          |
tappa            | character varying(254)          |           |          |
regione          | character varying(254)          |           |          |
km               | double precision                |           |          |
partenza         | character varying(254)          |           |          |
quota_part       | double precision                |           |          |
arrivo           | character varying(254)          |           |          |
quota_arri       | double precision                |           |          |
d+               | double precision                |           |          |
d-               | double precision                |           |          |
referente        | character varying(254)          |           |          |
telefono         | character varying(254)          |           |          |
email            | character varying(254)          |           |          |
verificata       | character varying               |           |          |
openstreetmap    | character varying               |           |          |
file_gpx         | character varying               |           |          |
pagina_web       | character varying               |           |          |
congruenza       | character varying               |           |          |
percorribilità   | character varying               |           |          |
segnaletica      | character varying               |           |          |
descrizione      | character varying               |           |          |
verifica         | character varying               |           |          |
difficolta       | character varying               |           |          |
data             | date                            |           |          |
Note             | character varying               |           |          |
descrizione_sito | character varying               |           |          |
immagine         | text                            |           |          |
Segnalazioni     | text                            |           |          |
Indexes:
"SI_Tappe_pkey" PRIMARY KEY, btree (id_2)
"sidx_SI_Tappe_geom" gist (geom)

 *
 * @return array
 */
class OutSourceSentieroItaliaProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(OutSourceSentieroItaliaProvider::class, function ($app) {
            return new OutSourceSentieroItaliaProvider($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {}

    /**
     * Get id lists from Sentiero Italia DB
     * SQL: select count(*) from sentiero_italia."SI_Tappe";
     *
     * Test on tinker:
     * tinker>>> $si = app(App\Providers\OutSourceSentieroItaliaProvider::class);
     * tinker>>> $si->getTrackList();
     */
    public function getTrackList(): array
    {
        $db = DB::connection('out_source_sicai');
        $ids = $db->table('sentiero_italia.SI_Tappe')
            ->select('id_2')
            ->get()
            ->pluck('id_2')
            ->toArray();

        return $ids;
    }

    /**
     * It downloads data from SICAI
     * tinker>>> $si = app(App\Providers\OutSourceSentieroItaliaProvider::class);
     * tinker>>> $si->getItem(531);
     */
    public function getItem(string $id): array
    {
        $data = [];
        $db = DB::connection('out_source_sicai');
        $item = $db->table('sentiero_italia.SI_Tappe')
            ->where('id_2', $id)
            ->select([
                'tappa',
                'descrizione_sito',
                'difficolta',
                'partenza',
                'arrivo',
            ])
            ->first();
        if (! is_null($item)) {
            // ADD Geometry
            $geometry = null;
            $res = $db->select(DB::raw('select st_asgeojson(ST_transform(geom,4326)) as geometry from sentiero_italia."SI_Tappe" where id_2='.$id));
            if (isset($res[0]->geometry)) {
                $geometry = $res[0]->geometry;
            }

            $data = [
                'provider' => get_class($this),
                'source_id' => $id,
                'tags' => [
                    'name' => ['it' => "Sentiero Italia CAI: tappa {$item->tappa}, da {$item->partenza} a {$item->arrivo}"],
                    'description' => ['it' => $item->descrizione_sito],
                    'cai_scale' => $item->difficolta,
                    'from' => $item->partenza,
                    'to' => $item->arrivo,
                ],
                'geometry' => $geometry,
            ];
        }

        return $data;
    }
}
