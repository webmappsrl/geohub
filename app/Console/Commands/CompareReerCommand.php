<?php

namespace App\Console\Commands;

use App\Exports\ReerMatchingWorkbookExport;
use App\Models\App;
use App\Models\EcTrack;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Generator;
use Throwable;

class CompareReerCommand extends Command
{
    /**
     * Confronto geometrico PostGIS tra tracce GeoHub e REER (GeoJSON ufficiale).
     *
     * Default ticket: utente pec@webmapp.it; opzionale perimetro app via layer.
     */
    protected $signature = 'geohub:compare-reer
        {geojson : Path del file GeoJSON REER}
        {--scope=user : Perimetro tracce: user (email) oppure app (layer)}
        {--email=pec@webmapp.it : Email utente (con scope=user)}
        {--app-id= : ID app GeoHub (con scope=app)}
        {--dwithin-m=50 : Tolleranza ST_DWithin in metri}
        {--hausdorff-m=20 : Soglia Hausdorff (metri) per "presente e aggiornato"}
        {--topn=10 : Candidati REER (KNN) su cui calcolare Hausdorff}
        {--out-xlsx=report_reer_geohub.xlsx : Workbook Excel in storage/app}
        {--out-csv= : (Opzionale) CSV foglio principale in storage/app}
        {--out-md= : (Opzionale) Relazione Markdown in storage/app}
        {--base-edit-url=https://geohub.webmapp.it : Base URL per LINK_EDIT_GEOHUB}
    ';

    protected $description = 'Confronto REER vs GeoHub (PostGIS): Excel multi-foglio + relazione matching';

    public function handle(): int
    {
        $geojsonPath = (string) $this->argument('geojson');
        $scope = strtolower(trim((string) $this->option('scope')));
        $email = (string) $this->option('email');
        $appIdOpt = $this->option('app-id');
        $resolvedAppId = null;
        $dwithinM = (float) $this->option('dwithin-m');
        $hausdorffOkM = (float) $this->option('hausdorff-m');
        $topN = max(1, (int) $this->option('topn'));
        $outXlsx = (string) $this->option('out-xlsx');
        $outCsv = (string) ($this->option('out-csv') ?? '');
        $outMd = (string) ($this->option('out-md') ?? '');
        $baseEditUrl = rtrim((string) $this->option('base-edit-url'), '/');

        if (! in_array($scope, ['user', 'app'], true)) {
            $this->error('scope deve essere "user" oppure "app"');

            return 1;
        }

        if (! file_exists($geojsonPath)) {
            $this->error("File GeoJSON non trovato: {$geojsonPath}");

            return 1;
        }

        @ini_set('memory_limit', '1024M');

        $user = null;
        if ($scope === 'user') {
            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                $this->error("Utente non trovato: {$email}");

                return 1;
            }
            $this->info("Scope: utente {$email} (user_id={$user->id})");
        } else {
            if ($appIdOpt === null || $appIdOpt === '') {
                $this->error('Con scope=app è obbligatorio --app-id=');

                return 1;
            }
            $resolvedAppId = (int) $appIdOpt;
            $app = App::with('layers')->find($resolvedAppId);
            if (! $app) {
                $this->error("App non trovata: {$resolvedAppId}");

                return 1;
            }
            $this->info("Scope: app id={$resolvedAppId} ({$app->name})");
        }

        $this->info("REER GeoJSON: {$geojsonPath}");

        DB::disableQueryLog();
        $conn = DB::connection();

        $csvFp = null;
        $csvOutPath = null;
        if (is_string($outCsv) && trim($outCsv) !== '') {
            $csvOutPath = storage_path('app/'.ltrim(trim($outCsv), '/'));
            $csvFp = fopen($csvOutPath, 'wb');
            if (! $csvFp) {
                $this->error("Impossibile creare CSV: {$csvOutPath}");

                return 1;
            }
            fputcsv($csvFp, ['ID_GEOHUB', 'LINK_EDIT_GEOHUB', 'REER_CHECK', 'REER_KMZ']);
        }

        try {
            $conn->beginTransaction();

            $this->info('Creo tabella temporanea e carico geometrie REER in PostGIS...');
            $conn->statement('DROP TABLE IF EXISTS tmp_reer_features');
            $conn->statement('
                CREATE TEMP TABLE tmp_reer_features (
                    id serial PRIMARY KEY,
                    id_percorso text,
                    download_kmz text,
                    geom geometry(MultiLineString, 4326)
                )
            ');

            $inserted = 0;
            foreach ($this->streamGeoJsonFeatures($geojsonPath) as $feature) {
                $props = $feature['properties'] ?? null;
                $geom = $feature['geometry'] ?? null;
                if (! is_array($props) || ! is_array($geom)) {
                    continue;
                }

                $idPercorso = $props['ID_PERCORSO'] ?? null;
                $downloadKmz = $props['DOWNLOAD_KMZ'] ?? null;
                $geomJson = json_encode($geom);
                if (! is_string($geomJson)) {
                    continue;
                }

                $conn->insert(
                    'INSERT INTO tmp_reer_features (id_percorso, download_kmz, geom)
                     VALUES (?, ?, ST_SetSRID(ST_GeomFromGeoJSON(?), 4326))',
                    [
                        (is_string($idPercorso) && trim($idPercorso) !== '') ? trim($idPercorso) : null,
                        (is_string($downloadKmz) && trim($downloadKmz) !== '') ? trim($downloadKmz) : null,
                        $geomJson,
                    ]
                );
                $inserted++;

                if ($inserted % 1000 === 0) {
                    $this->info(" - Caricate {$inserted} features REER...");
                }
            }
            $this->info("Features REER caricate in PostGIS: {$inserted}");

            if ($scope === 'user') {
                $this->info('Recupero tracce GEOHUB per user_id...');
                $trackIds = EcTrack::query()
                    ->where('user_id', $user->id)
                    ->whereNotNull('geometry')
                    ->orderBy('id')
                    ->pluck('id')
                    ->all();
            } else {
                $this->info('Recupero tracce GEOHUB dai layer dell\'app...');
                $trackIds = array_keys($app->getTracksFromLayer());
                sort($trackIds);
                $trackIds = array_values(array_filter($trackIds, static fn ($id) => $id !== null && $id !== ''));
                $trackIds = EcTrack::query()
                    ->whereIn('id', $trackIds)
                    ->whereNotNull('geometry')
                    ->orderBy('id')
                    ->pluck('id')
                    ->all();
            }

            $totalTracks = count($trackIds);
            $this->info("Tracce GeoHub con geometria: {$totalTracks}");

            if ($totalTracks === 0) {
                $this->warn('Nessuna traccia GeoHub nel perimetro: workbook con solo REER e riepilogo.');
                $reerUnmatched = $conn->select('
                    SELECT COALESCE(id_percorso, id::text) AS id_percorso, download_kmz
                    FROM tmp_reer_features
                    ORDER BY id
                ');
                $reerSenzaGeohubRows = [];
                foreach ($reerUnmatched as $u) {
                    $reerSenzaGeohubRows[] = [
                        (string) $u->id_percorso,
                        is_string($u->download_kmz ?? null) ? (string) $u->download_kmz : '',
                    ];
                }
                $summaryRows = [
                    ['Data', now()->toIso8601String()],
                    ['Scope', $scope],
                    ['Email', $scope === 'user' ? $email : '—'],
                    ['App ID', $resolvedAppId !== null ? (string) $resolvedAppId : '—'],
                    ['GeoJSON REER', $geojsonPath],
                    ['Features REER caricate', (string) $inserted],
                    ['Tracce GeoHub confrontate', '0'],
                    ['dwithin_m', (string) $dwithinM],
                    ['hausdorff_ok_m', (string) $hausdorffOkM],
                    ['topn', (string) $topN],
                    ['Nota', 'Nessuna traccia con geometria nel perimetro scelto'],
                ];
                $conn->commit();
                if ($csvFp) {
                    fclose($csvFp);
                }
                $xlsxRelPath = ltrim($outXlsx, '/');
                Excel::store(
                    new ReerMatchingWorkbookExport(
                        [],
                        $summaryRows,
                        [],
                        $reerSenzaGeohubRows,
                        []
                    ),
                    $xlsxRelPath,
                    'local'
                );
                $this->info('Excel generato: '.storage_path('app/'.$xlsxRelPath));
                if (trim($outMd) !== '') {
                    $mdPath = storage_path('app/'.ltrim(trim($outMd), '/'));
                    $this->writeMarkdownReport($mdPath, $summaryRows, [], $reerSenzaGeohubRows, []);
                    $this->info("Markdown: {$mdPath}");
                }
                $this->info('Done.');

                return 0;
            }

            $conn->statement('DROP TABLE IF EXISTS tmp_geohub_tracks');
            $conn->statement('
                CREATE TEMP TABLE tmp_geohub_tracks (
                    track_id bigint PRIMARY KEY,
                    geom_3857 geometry(LineString, 3857)
                )
            ');

            foreach (array_chunk($trackIds, 1000) as $chunk) {
                $ids = implode(',', array_map('intval', $chunk));
                $conn->statement("
                    INSERT INTO tmp_geohub_tracks (track_id, geom_3857)
                    SELECT
                        id AS track_id,
                        ST_Transform(ST_Force2D(ST_SetSRID(geometry, 4326)), 3857) AS geom_3857
                    FROM ec_tracks
                    WHERE id IN ({$ids})
                ");
            }

            $conn->statement('CREATE INDEX tmp_geohub_tracks_geom_gix ON tmp_geohub_tracks USING GIST (geom_3857)');
            $conn->statement('ANALYZE tmp_geohub_tracks');

            $conn->statement('DROP TABLE IF EXISTS tmp_reer_features_3857');
            $conn->statement('
                CREATE TEMP TABLE tmp_reer_features_3857 AS
                SELECT
                    id,
                    id_percorso,
                    download_kmz,
                    ST_Transform(ST_Force2D(geom), 3857) AS geom_3857
                FROM tmp_reer_features
            ');

            $conn->statement('CREATE INDEX tmp_reer_features_3857_geom_gix ON tmp_reer_features_3857 USING GIST (geom_3857)');
            $conn->statement('ANALYZE tmp_reer_features_3857');

            $this->info('Matching spaziale (ST_DWithin + Hausdorff su top candidati)...');
            $result = $conn->select("
                SELECT
                    t.track_id,
                    b.reer_id,
                    b.id_percorso,
                    b.download_kmz,
                    b.hausdorff_m
                FROM tmp_geohub_tracks t
                LEFT JOIN LATERAL (
                    SELECT reer_id, id_percorso, download_kmz, hausdorff_m
                    FROM (
                        SELECT
                            r.id AS reer_id,
                            r.id_percorso,
                            r.download_kmz,
                            ST_HausdorffDistance(t.geom_3857, r.geom_3857) AS hausdorff_m
                        FROM tmp_reer_features_3857 r
                        WHERE ST_DWithin(t.geom_3857, r.geom_3857, {$dwithinM})
                        ORDER BY t.geom_3857 <-> r.geom_3857
                        LIMIT {$topN}
                    ) c
                    ORDER BY hausdorff_m ASC
                    LIMIT 1
                ) b ON TRUE
                ORDER BY t.track_id
            ");

            $this->info('Rilevo match ambigui (più candidati entro buffer)...');
            $ambigui = $conn->select("
                SELECT t.track_id, COUNT(*)::int AS candidati
                FROM tmp_geohub_tracks t
                INNER JOIN tmp_reer_features_3857 r ON ST_DWithin(t.geom_3857, r.geom_3857, {$dwithinM})
                GROUP BY t.track_id
                HAVING COUNT(*) > 1
                ORDER BY candidati DESC, t.track_id
            ");

            $mainRows = [];
            $matchedReerInternalIds = [];
            $counts = [
                'presente e aggiornato' => 0,
                'assente' => 0,
                'presente da aggiornare' => 0,
            ];

            foreach ($result as $row) {
                $trackId = (int) $row->track_id;
                $linkEdit = "{$baseEditUrl}/resources/ec-tracks/{$trackId}/edit";

                $status = 'assente';
                $kmz = '';
                if (isset($row->hausdorff_m) && $row->hausdorff_m !== null) {
                    $hausdorff = (float) $row->hausdorff_m;
                    $status = $hausdorff <= $hausdorffOkM ? 'presente e aggiornato' : 'presente da aggiornare';
                    $kmz = is_string($row->download_kmz ?? null) ? (string) $row->download_kmz : '';
                    if (isset($row->reer_id) && $row->reer_id !== null) {
                        $matchedReerInternalIds[(int) $row->reer_id] = true;
                    }
                }

                $counts[$status]++;
                $mainRows[] = [$trackId, $linkEdit, $status, $kmz];

                if ($csvFp) {
                    fputcsv($csvFp, [$trackId, $linkEdit, $status, $kmz]);
                }
            }

            $matchedIdsList = array_keys($matchedReerInternalIds);
            $placeholders = count($matchedIdsList) > 0 ? implode(',', array_map('intval', $matchedIdsList)) : '0';

            $reerUnmatched = $conn->select("
                SELECT
                    COALESCE(id_percorso, id::text) AS id_percorso,
                    download_kmz
                FROM tmp_reer_features
                WHERE id NOT IN ({$placeholders})
                ORDER BY id
            ");

            $geohubSenzaReer = [];
            foreach ($mainRows as $r) {
                if ($r[2] === 'assente') {
                    $geohubSenzaReer[] = [$r[0], $r[1]];
                }
            }

            $ambiguiRows = [];
            foreach ($ambigui as $a) {
                $tid = (int) $a->track_id;
                $ambiguiRows[] = [
                    $tid,
                    "{$baseEditUrl}/resources/ec-tracks/{$tid}/edit",
                    (int) $a->candidati,
                ];
            }

            $reerSenzaGeohubRows = [];
            foreach ($reerUnmatched as $u) {
                $reerSenzaGeohubRows[] = [
                    (string) $u->id_percorso,
                    is_string($u->download_kmz ?? null) ? (string) $u->download_kmz : '',
                ];
            }

            $conn->commit();

            if ($csvFp) {
                fclose($csvFp);
                $this->info("CSV generato: {$csvOutPath}");
            }

            $summaryRows = [
                ['Data', now()->toIso8601String()],
                ['Scope', $scope],
                ['Email', $scope === 'user' ? $email : '—'],
                ['App ID', $resolvedAppId !== null ? (string) $resolvedAppId : '—'],
                ['GeoJSON REER', $geojsonPath],
                ['Features REER caricate', (string) $inserted],
                ['Tracce GeoHub confrontate', (string) $totalTracks],
                ['dwithin_m', (string) $dwithinM],
                ['hausdorff_ok_m', (string) $hausdorffOkM],
                ['topn', (string) $topN],
                ['presente e aggiornato', (string) $counts['presente e aggiornato']],
                ['assente', (string) $counts['assente']],
                ['presente da aggiornare', (string) $counts['presente da aggiornare']],
                ['REER non abbinate a nessuna traccia', (string) count($reerSenzaGeohubRows)],
                ['Tracce con più candidati nel buffer', (string) count($ambiguiRows)],
            ];

            $xlsxRelPath = ltrim($outXlsx, '/');
            $this->info('Genero workbook Excel...');
            Excel::store(
                new ReerMatchingWorkbookExport(
                    $mainRows,
                    $summaryRows,
                    $geohubSenzaReer,
                    $reerSenzaGeohubRows,
                    $ambiguiRows
                ),
                $xlsxRelPath,
                'local'
            );
            $this->info('Excel generato: '.storage_path('app/'.$xlsxRelPath));

            if (trim($outMd) !== '') {
                $mdPath = storage_path('app/'.ltrim(trim($outMd), '/'));
                $this->writeMarkdownReport(
                    $mdPath,
                    $summaryRows,
                    $geohubSenzaReer,
                    $reerSenzaGeohubRows,
                    $ambiguiRows
                );
                $this->info("Markdown: {$mdPath}");
            }

            $this->info('Done.');

            return 0;
        } catch (Throwable $e) {
            try {
                if ($conn->transactionLevel() > 0) {
                    $conn->rollBack();
                }
            } catch (Throwable $ignored) {
            }
            if ($csvFp) {
                fclose($csvFp);
            }
            $this->error($e->getMessage());

            return 1;
        }
    }

    /**
     * @param  array<int, array{0: string, 1: string|int|float}>  $summaryRows
     * @param  array<int, array<int, mixed>>  $geohubSenzaReer
     * @param  array<int, array<int, mixed>>  $reerSenzaGeohub
     * @param  array<int, array<int, mixed>>  $ambigui
     */
    private function writeMarkdownReport(
        string $path,
        array $summaryRows,
        array $geohubSenzaReer,
        array $reerSenzaGeohub,
        array $ambigui
    ): void {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $lines = [
            '# Relazione matching GeoHub vs REER',
            '',
            '## Parametri e conteggi',
            '',
        ];
        foreach ($summaryRows as [$k, $v]) {
            $lines[] = '- **'.$k.'**: '.$v;
        }
        $lines[] = '';
        $lines[] = '## GeoHub senza corrispondenza REER (assenti)';
        $lines[] = '';
        $lines[] = 'Totale righe: '.count($geohubSenzaReer);
        $lines[] = '';
        $lines[] = '## REER senza traccia GeoHub nel perimetro scelto';
        $lines[] = '';
        $lines[] = 'Totale righe: '.count($reerSenzaGeohub);
        $lines[] = '';
        $lines[] = '## Match potenzialmente ambigui (più geometrie REER nel buffer)';
        $lines[] = '';
        $lines[] = 'Totale tracce: '.count($ambigui);
        $lines[] = '';

        file_put_contents($path, implode("\n", $lines));
    }

    /**
     * @return Generator<int, array<string, mixed>>
     */
    private function streamGeoJsonFeatures(string $path): Generator
    {
        $fp = fopen($path, 'rb');
        if (! $fp) {
            throw new \RuntimeException("Impossibile aprire il file: {$path}");
        }

        $buffer = '';
        $inFeaturesArray = false;
        $inString = false;
        $escape = false;
        $depth = 0;
        $collecting = false;
        $obj = '';

        while (! feof($fp)) {
            $chunk = fread($fp, 1024 * 1024);
            if ($chunk === false) {
                break;
            }
            $buffer .= $chunk;

            $len = strlen($buffer);
            for ($i = 0; $i < $len; $i++) {
                $ch = $buffer[$i];

                if (! $inFeaturesArray) {
                    if (substr($buffer, $i, 10) === '"features"') {
                        $posBracket = strpos($buffer, '[', $i);
                        if ($posBracket !== false) {
                            $inFeaturesArray = true;
                            $i = $posBracket;
                        }
                    }
                    continue;
                }

                if ($collecting) {
                    $obj .= $ch;
                }

                if ($escape) {
                    $escape = false;
                    continue;
                }
                if ($ch === '\\') {
                    if ($inString) {
                        $escape = true;
                    }
                    continue;
                }
                if ($ch === '"') {
                    $inString = ! $inString;
                    continue;
                }
                if ($inString) {
                    continue;
                }

                if (! $collecting) {
                    if ($ch === '{') {
                        $collecting = true;
                        $depth = 1;
                        $obj = '{';
                    } elseif ($ch === ']') {
                        fclose($fp);

                        return;
                    }
                    continue;
                }

                if ($ch === '{') {
                    $depth++;
                } elseif ($ch === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $feature = json_decode($obj, true);
                        if (is_array($feature)) {
                            yield $feature;
                        }
                        $collecting = false;
                        $obj = '';
                    }
                }
            }

            $buffer = $collecting ? '' : substr($buffer, max(0, $len - 1024));
        }

        fclose($fp);
    }
}
