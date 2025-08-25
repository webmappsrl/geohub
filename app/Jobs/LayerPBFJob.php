<?php

namespace App\Jobs;

class LayerPBFJob extends TrackPBFJob
{
    public function __construct($z, $x, $y, $app_id, $author_id)
    {
        parent::__construct($z, $x, $y, $app_id, $author_id);
    }

    /**
     * Override di generateSQL per creare un'unica geometria per layer.
     *
     * @param  array  $boundingBox
     * @param  array  $associatedLayerMap
     */
    protected function generateSQL($boundingBox): string
    {
        // Recupera l'app con i layer associati
        $app = \App\Models\App::with('layers')->find($this->app_id);
        if (! $app) {
            throw new \Exception("App not found: {$this->app_id}");
        }

        $layerIds = $app->layers->pluck('id')->toArray();
        if (empty($layerIds)) {
            throw new \Exception("No layers associated with app: {$this->app_id}");
        }
        // Genera l'elenco degli ID layer come stringa SQL
        $layerIdsSQL = implode(', ', $layerIds);

        $tbl = [
            'srid' => '4326',
            'geomColumn' => 'geometry',
            'attrColumns' => 'JSON_BUILD_ARRAY(l.id) AS layers,                -- Usa ARRAY per garantire un array anche con un solo elemento
                 l.color AS stroke_color',
        ];

        // Trasforma il bounding box in una stringa SQL valida
        $boundingBoxSQL = sprintf(
            'ST_MakeEnvelope(%f, %f, %f, %f, 3857)',
            $boundingBox['xmin'],
            $boundingBox['ymin'],
            $boundingBox['xmax'],
            $boundingBox['ymax']
        );

        return <<<SQL
        WITH 
        bounds AS (
            SELECT {$boundingBoxSQL} AS geom, {$boundingBoxSQL}::box2d AS b2d ),
        mvtgeom AS (
            SELECT 
                ST_AsMVTGeom(
                    ST_SimplifyPreserveTopology(
                        ST_Transform(ST_Force2D(ec.{$tbl['geomColumn']}), 3857), 4
                    ), 
                    bounds.b2d
                ) AS geom,
                {$tbl['attrColumns']}
            FROM layers l
            JOIN ec_track_layer etl ON l.id = etl.layer_id
            JOIN ec_tracks ec ON etl.ec_track_id = ec.id
            CROSS JOIN bounds
            WHERE l.id IN ({$layerIdsSQL}) -- Filtra per i layer associati all'app
                AND 
                ST_Intersects(
                    ST_Transform(ST_Force2D(ec.{$tbl['geomColumn']}), 3857),
                    bounds.geom
                )
                AND ST_IsValid(ec.{$tbl['geomColumn']}) 
                AND ST_Dimension(ec.{$tbl['geomColumn']}) > 0
                AND NOT ST_IsEmpty(ec.{$tbl['geomColumn']})
                AND ec.{$tbl['geomColumn']} IS NOT NULL
            ORDER BY l.rank ASC -- Ordina i risultati per rank del layer
        )
        SELECT ST_AsMVT(mvtgeom.*, 'layers') FROM mvtgeom;
        SQL;
    }
}
