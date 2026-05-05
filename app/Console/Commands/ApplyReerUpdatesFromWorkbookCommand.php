<?php

namespace App\Console\Commands;

use App\Console\Concerns\StreamsReerGeoJsonFeatures;
use App\Jobs\GeneratePBFByTrackJob;
use App\Jobs\UpdateCurrentDataJob;
use App\Jobs\UpdateEcTrack3DDemJob;
use App\Jobs\UpdateEcTrackAwsJob;
use App\Jobs\UpdateEcTrackDemJob;
use App\Jobs\UpdateEcTrackElasticIndexJob;
use App\Jobs\UpdateEcTrackGenerateElevationChartImage;
use App\Jobs\UpdateEcTrackOrderRelatedPoi;
use App\Jobs\UpdateEcTrackSlopeValues;
use App\Jobs\UpdateLayerTracksJob;
use App\Jobs\UpdateManualDataJob;
use App\Jobs\UpdateModelWithGeometryTaxonomyWhere;
use App\Jobs\UpdateTrackPBFInfoJob;
use App\Models\EcTrack;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Applica geometrie alle tracce GeoHub marcate "presente da aggiornare" nel workbook
 * generato da geohub:compare-reer. Fonte geometria consigliata: stesso GeoJSON REER
 * usato nel confronto (--geojson); in alternativa download da REER_KMZ (KMZ ArcGIS).
 */
class ApplyReerUpdatesFromWorkbookCommand extends Command
{
    use StreamsReerGeoJsonFeatures;

    private const STATUS_UPDATE = 'presente da aggiornare';

    private const STATUS_UPDATED = 'presente e aggiornato';

    private const SHEET_SUMMARY = 'Riepilogo';

    protected $signature = 'geohub:apply-reer-from-workbook
        {workbook : Percorso al file .xlsx (es. export da Google Sheets o storage/app/...)}
        {--sheet=Tracce : Nome del foglio con colonne ID_GEOHUB, REER_CHECK, REER_KMZ}
        {--geojson= : Stesso GeoJSON REER usato in compare-reer (consigliato: geometrie da file)}
        {--dry-run : Elenca le righe da aggiornare senza modificare il database}
        {--limit=0 : Elabora al massimo N tracce (0 = tutte)}
        {--timeout=120 : Timeout HTTP in secondi per scaricare ogni KMZ}
        {--post-apply-output= : Workbook di uscita (default: storage/app/reer_report_post_apply_<data>.xlsx)}
        {--no-post-export : Non scrivere il workbook dopo l\'apply (solo DB + job)}
    ';

    protected $description = 'Applica geometria REER sulle tracce "presente da aggiornare" (GeoJSON e/o KMZ) e salva il report aggiornato';

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->argument('workbook'));
        $sheetName = (string) $this->option('sheet');
        $geojsonOpt = $this->option('geojson');
        $geojsonPath = is_string($geojsonOpt) && $geojsonOpt !== ''
            ? $this->resolvePath($geojsonOpt)
            : '';
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $timeout = max(5, (int) $this->option('timeout'));
        $noPostExport = (bool) $this->option('no-post-export');
        $postApplyOutputOpt = $this->option('post-apply-output');
        $postApplyOutput = is_string($postApplyOutputOpt) && $postApplyOutputOpt !== ''
            ? $this->resolvePath($postApplyOutputOpt)
            : storage_path('app/reer_report_post_apply_'.Carbon::now()->format('Ymd_His').'.xlsx');

        if ($geojsonPath !== '' && ! is_readable($geojsonPath)) {
            $this->error("File GeoJSON non leggibile: {$geojsonPath}");

            return 1;
        }

        if (! is_readable($path)) {
            $this->error("File non leggibile: {$path}");
            $this->warn('Export dal foglio Google: File → Scarica → Microsoft Excel (.xlsx), oppure salva il workbook generato da geohub:compare-reer.');

            return 1;
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName($sheetName);
        if (! $sheet) {
            $this->error("Foglio non trovato: {$sheetName}");
            $this->line('Fogli disponibili: '.implode(', ', $spreadsheet->getSheetNames()));

            return 1;
        }

        $rows = $sheet->toArray();
        if ($rows === []) {
            $this->warn('Foglio vuoto.');

            return 0;
        }

        $header = array_shift($rows);
        $colIndex = $this->resolveTracceColumnIndexes($header, $rows);
        $useGeojson = $geojsonPath !== '';
        if ($colIndex['id'] === null || $colIndex['check'] === null) {
            $this->error('Intestazioni attese: ID_GEOHUB, REER_CHECK (e REER_KMZ se non usi --geojson). Trovato: '.json_encode($header));

            return 1;
        }
        if (! $useGeojson && $colIndex['kmz'] === null) {
            $this->error('Colonna REER_KMZ richiesta senza --geojson, oppure passa --geojson= per usare le geometrie dal file ufficiale.');

            return 1;
        }

        $candidates = [];
        foreach ($rows as $idx => $row) {
            $checkRaw = $colIndex['check'] !== null ? ($row[$colIndex['check']] ?? null) : null;
            $checkNorm = $this->normalizeStatus($checkRaw);
            if ($checkNorm !== self::STATUS_UPDATE) {
                continue;
            }
            $idRaw = $row[$colIndex['id']] ?? null;
            $trackId = $this->parseTrackId($idRaw);
            if ($trackId === null) {
                $this->warn('Riga '.($idx + 2).': ID_GEOHUB non valido, skip.');
                continue;
            }
            $kmz = ($colIndex['kmz'] !== null) ? trim((string) ($row[$colIndex['kmz']] ?? '')) : '';
            $idPercorsoCell = ($colIndex['id_percorso'] !== null) ? trim((string) ($row[$colIndex['id_percorso']] ?? '')) : '';
            $idPercorso = $idPercorsoCell !== '' ? $idPercorsoCell : $this->parseIdPercorsoFromKmzUrl($kmz);
            if ($idPercorso !== null) {
                $idPercorso = trim($idPercorso);
            } else {
                $idPercorso = '';
            }

            if ($useGeojson) {
                if ($idPercorso === '') {
                    $this->warn("Traccia {$trackId}: impossibile ricavare ID percorso REER dall'URL REER_KMZ (serve con --geojson), skip.");

                    continue;
                }
                if ($kmz !== '' && ! filter_var($kmz, FILTER_VALIDATE_URL)) {
                    $this->warn("Traccia {$trackId}: REER_KMZ non URL valido (si usa solo GeoJSON).");
                }
            } else {
                if ($kmz === '' || ! filter_var($kmz, FILTER_VALIDATE_URL)) {
                    $this->warn("Traccia {$trackId}: REER_KMZ assente o URL non valido, skip.");

                    continue;
                }
            }

            $candidates[] = ['track_id' => $trackId, 'kmz' => $kmz, 'id_percorso' => $idPercorso];
        }

        $total = count($candidates);
        if ($limit > 0) {
            $candidates = array_slice($candidates, 0, $limit);
        }

        $sourceNote = $useGeojson
            ? " (--geojson: {$geojsonPath})"
            : ' (solo KMZ)';
        $this->info('Righe "presente da aggiornare" da elaborare: '.$total.' (eseguo: '.count($candidates).')'.$sourceNote);

        if ($candidates === []) {
            return 0;
        }

        $geomIndex = [];
        if ($useGeojson && $candidates !== []) {
            $needed = [];
            foreach ($candidates as $c) {
                $needed[strtolower($c['id_percorso'])] = true;
            }
            $geomIndex = $this->loadReerGeometriesByIdPercorso($geojsonPath, $needed);
            $this->info('Geometrie caricate dal GeoJSON per ID_PERCORSO richiesti: '.count($geomIndex));
        }

        if ($dryRun) {
            foreach ($candidates as $c) {
                $ip = $c['id_percorso'];
                $kmz = $c['kmz'];
                $this->line("[dry-run] track_id={$c['track_id']} id_percorso={$ip} kmz={$kmz}");
            }
            $this->info('Nessuna modifica (dry-run).');

            return 0;
        }

        $ok = 0;
        $fail = 0;
        /** @var list<int> */
        $successfulTrackIds = [];
        foreach ($candidates as $c) {
            $trackId = $c['track_id'];
            $kmzUrl = $c['kmz'];
            try {
                $track = EcTrack::query()->find($trackId);
                if (! $track) {
                    $this->error("Traccia {$trackId}: non trovata.");
                    $fail++;

                    continue;
                }

                $geometry = null;
                if ($useGeojson && $c['id_percorso'] !== '') {
                    $gKey = strtolower($c['id_percorso']);
                    if (isset($geomIndex[$gKey])) {
                        $geometry = $this->geometryWktFromGeoJson($geomIndex[$gKey]);
                    }
                }

                if ($geometry === null && $kmzUrl !== '') {
                    $payload = $this->fetchKmlOrRawXml($kmzUrl, $timeout);
                    if ($payload === null || $payload === '') {
                        $this->error("Traccia {$trackId}: download KMZ/KML fallito o archivio non valido.");
                        $fail++;

                        continue;
                    }
                    $geometry = $track->fileToGeometry($payload);
                }

                if ($geometry === null) {
                    $this->error("Traccia {$trackId}: impossibile ottenere la geometria (GeoJSON/KMZ).");
                    $fail++;

                    continue;
                }

                EcTrack::withoutEvents(function () use ($track, $geometry) {
                    $track->geometry = $geometry;
                    $track->save();
                });

                $track->refresh();
                $this->dispatchGeometryFollowUpChain($track);
                $this->info("Traccia {$trackId}: geometria aggiornata da REER (code post-processing accodati).");
                $ok++;
                $successfulTrackIds[] = $trackId;
            } catch (Throwable $e) {
                Log::error('geohub:apply-reer-from-workbook track '.$trackId.': '.$e->getMessage(), ['e' => $e]);
                $this->error("Traccia {$trackId}: ".$e->getMessage());
                $fail++;
            }
        }

        if (! $noPostExport && $successfulTrackIds !== []) {
            try {
                $changed = $this->writePostApplyWorkbook($path, $postApplyOutput, $sheetName, $successfulTrackIds);
                $this->info("Workbook post-apply salvato ({$changed} righe aggiornate in colonna REER_CHECK): {$postApplyOutput}");
                if ($fail > 0) {
                    $this->warn("Apply parziale: nel workbook restano \"presente da aggiornare\" le tracce non riuscite (errori={$fail}).");
                }
            } catch (Throwable $e) {
                Log::error('geohub:apply-reer-from-workbook post-export: '.$e->getMessage(), ['e' => $e]);
                $this->error('Export workbook post-apply fallito: '.$e->getMessage());
                $this->warn('Le tracce sono state aggiornate su GeoHub; correggi il report Excel a mano o usa geohub:export-reer-workbook-post-apply.');

                return 1;
            }
        }

        $this->info("Completato. OK={$ok}, errori={$fail}");

        return $fail > 0 ? 1 : 0;
    }

    /**
     * Gestisce workbook legacy con 5 colonne dati (id, uuid, link, stato, kmz) ma intestazione
     * a 4 etichette senza colonna dedicata allo UUID: le etichette risultano sfalsate.
     *
     * @param  list<mixed>  $headerRow
     * @param  array<int, list<mixed>>  $dataRows
     * @return array{id: int|null, id_percorso: int|null, check: int|null, kmz: int|null}
     */
    private function resolveTracceColumnIndexes(array $headerRow, array $dataRows): array
    {
        $colIndex = $this->mapHeaderRow($headerRow);
        if ($colIndex['id'] === null || $colIndex['check'] === null) {
            return $colIndex;
        }
        if ($colIndex['id_percorso'] !== null) {
            return $colIndex;
        }
        $probeCol = $colIndex['check'];
        foreach ($dataRows as $row) {
            $atProbe = isset($row[$probeCol]) ? trim((string) $row[$probeCol]) : '';
            $at3 = isset($row[3]) ? trim((string) $row[3]) : '';
            $at4 = isset($row[4]) ? trim((string) $row[4]) : '';
            if ($atProbe !== '' && strpos($atProbe, 'http') === 0
                && $at3 !== '' && (stripos($at3, 'presente') !== false || stripos($at3, 'assente') !== false)
                && $at4 !== '' && strpos($at4, 'http') === 0) {
                $this->warn('Layout foglio Tracce: file Excel a 5 colonne con intestazioni vecchie (sfasamento) — uso stato col. 4 e KMZ col. 5. Rigenera il report con compare-reer per avere 4 colonne allineate.');

                return [
                    'id' => 0,
                    'id_percorso' => 1,
                    'check' => 3,
                    'kmz' => 4,
                ];
            }
        }

        return $colIndex;
    }

    private function resolvePath(string $path): string
    {
        if ($path !== '' && $path[0] === DIRECTORY_SEPARATOR) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @param  list<int>  $successfulTrackIds
     */
    private function writePostApplyWorkbook(
        string $sourcePath,
        string $outputPath,
        string $sheetName,
        array $successfulTrackIds
    ): int {
        $successSet = [];
        foreach ($successfulTrackIds as $id) {
            $successSet[(int) $id] = true;
        }

        $spreadsheet = IOFactory::load($sourcePath);
        $sheet = $spreadsheet->getSheetByName($sheetName);
        if (! $sheet) {
            throw new RuntimeException("Foglio non trovato: {$sheetName}");
        }

        $rows = $sheet->toArray();
        if ($rows === []) {
            return 0;
        }
        $header = array_shift($rows);
        $colIndex = $this->resolveTracceColumnIndexes($header, $rows);
        if ($colIndex['id'] === null || $colIndex['check'] === null) {
            throw new RuntimeException('Intestazioni attese: ID_GEOHUB, REER_CHECK nel foglio tracce.');
        }

        $changed = 0;
        foreach ($rows as $idx => $row) {
            $trackId = $this->parseTrackId($row[$colIndex['id']] ?? null);
            if ($trackId === null || ! isset($successSet[$trackId])) {
                continue;
            }
            $checkNorm = $this->normalizeStatus($row[$colIndex['check']] ?? null);
            if ($checkNorm !== self::STATUS_UPDATE) {
                continue;
            }
            $sheet->setCellValueByColumnAndRow($colIndex['check'] + 1, $idx + 2, self::STATUS_UPDATED);
            $changed++;
        }

        $this->updateSummarySheet($spreadsheet, $changed);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);

        return $changed;
    }

    private function updateSummarySheet(Spreadsheet $spreadsheet, int $changed): void
    {
        if ($changed <= 0) {
            return;
        }
        $summary = $spreadsheet->getSheetByName(self::SHEET_SUMMARY);
        if (! $summary) {
            return;
        }

        $dataRow = Carbon::now()->timezone(config('app.timezone'))->toIso8601String();
        $rows = $summary->toArray();
        foreach ($rows as $rowIdx => $row) {
            $key = isset($row[0]) ? trim((string) $row[0]) : '';
            if ($key === 'Data') {
                $summary->setCellValueByColumnAndRow(2, $rowIdx + 1, $dataRow);
            }
            if ($key === self::STATUS_UPDATED) {
                $prev = isset($row[1]) ? (int) $row[1] : 0;
                $summary->setCellValueByColumnAndRow(2, $rowIdx + 1, $prev + $changed);
            }
            if ($key === self::STATUS_UPDATE) {
                $prev = isset($row[1]) ? (int) $row[1] : 0;
                $summary->setCellValueByColumnAndRow(2, $rowIdx + 1, max(0, $prev - $changed));
            }
        }
    }

    /**
     * @param  array<string, bool>  $neededLowercaseIds
     * @return array<string, array<string, mixed>>
     */
    private function loadReerGeometriesByIdPercorso(string $geojsonPath, array $neededLowercaseIds): array
    {
        $index = [];
        foreach ($this->streamReerGeoJsonFeatures($geojsonPath) as $feature) {
            $props = $feature['properties'] ?? null;
            $geom = $feature['geometry'] ?? null;
            if (! is_array($props) || ! is_array($geom)) {
                continue;
            }
            $idPercorso = $props['ID_PERCORSO'] ?? null;
            if (! is_string($idPercorso) || trim($idPercorso) === '') {
                continue;
            }
            $key = strtolower(trim($idPercorso));
            if (! isset($neededLowercaseIds[$key])) {
                continue;
            }
            $index[$key] = $geom;
        }

        return $index;
    }

    /**
     * WKT per assegnazione a EcTrack::geometry: il mutatore aggiunge "SRID=4326;" e non accetta DB::raw().
     *
     * @param  array<string, mixed>  $geometry
     */
    private function geometryWktFromGeoJson(array $geometry): string
    {
        $geomJson = json_encode($geometry);
        if (! is_string($geomJson)) {
            throw new RuntimeException('Impossibile serializzare geometria GeoJSON.');
        }
        $row = DB::selectOne(
            'SELECT ST_AsText(ST_Force3D(ST_LineMerge(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)))) AS wkt',
            [$geomJson]
        );
        if (! $row || ! isset($row->wkt) || ! is_string($row->wkt) || $row->wkt === '') {
            throw new RuntimeException('PostGIS: geometria GeoJSON non valida o vuota.');
        }

        return $row->wkt;
    }

    private function parseIdPercorsoFromKmzUrl(string $url): ?string
    {
        if (preg_match('/ID_PERCORSO%3D%27([^%\'\s]+)%27/i', $url, $m)) {
            return rawurldecode($m[1]);
        }
        $decoded = rawurldecode($url);
        if (preg_match("/ID_PERCORSO\\s*=\\s*'([^']+)'/i", $decoded, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * @param  list<mixed>  $headerRow
     * @return array{id: int|null, id_percorso: int|null, check: int|null, kmz: int|null}
     */
    private function mapHeaderRow(array $headerRow): array
    {
        $norm = static function ($v): string {
            return strtolower(trim((string) $v));
        };
        $map = [];
        foreach ($headerRow as $i => $label) {
            $map[$norm($label)] = $i;
        }

        return [
            'id' => $map['id_geohub'] ?? null,
            'id_percorso' => $map['id_percorso'] ?? null,
            'check' => $map['reer_check'] ?? null,
            'kmz' => $map['reer_kmz'] ?? null,
        ];
    }

    /**
     * @param  mixed  $value
     */
    private function normalizeStatus($value): string
    {
        $s = strtolower(trim((string) $value));
        $collapsed = preg_replace('/[\s_-]+/u', ' ', $s);
        if ($collapsed === 'presente da aggiornare') {
            return self::STATUS_UPDATE;
        }

        return $s;
    }

    /**
     * @param  mixed  $value
     */
    private function parseTrackId($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        return null;
    }

    /**
     * Scarica KMZ o KML; se ZIP, estrae il primo KML utile (preferenza doc.kml).
     */
    private function fetchKmlOrRawXml(string $url, int $timeout): ?string
    {
        $response = Http::timeout($timeout)
            ->withHeaders(['User-Agent' => 'GeoHub-REER-apply/1'])
            ->get($url);
        if (! $response->successful()) {
            return null;
        }
        $body = $response->body();
        $trim = ltrim($body);
        if (strpos($trim, '<?xml') === 0 || strpos($trim, '<kml') === 0) {
            return $body;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'reer_kmz_');
        if ($tmp === false) {
            return null;
        }
        file_put_contents($tmp, $body);
        $zip = new ZipArchive;
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);

            return null;
        }
        $kml = false;
        $docIdx = $zip->locateName('doc.kml');
        if ($docIdx !== false) {
            $kml = $zip->getFromIndex($docIdx);
        }
        if ($kml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (is_string($name) && preg_match('/\\.kml$/i', $name)) {
                    $kml = $zip->getFromIndex($i);
                    break;
                }
            }
        }
        $zip->close();
        @unlink($tmp);

        return $kml !== false ? $kml : null;
    }

    /**
     * Stessa catena di updateDataChain ma senza UpdateTrackFromOsmJob: la geometria REER non deve essere sovrascritta da OSM.
     */
    private function dispatchGeometryFollowUpChain(EcTrack $track): void
    {
        $chain = [];
        $layers = $track->associatedLayers;
        if ($layers && $layers->count() > 0) {
            foreach ($layers as $layer) {
                $chain[] = new UpdateLayerTracksJob($layer);
            }
        }
        $chain[] = new UpdateEcTrackDemJob($track);
        $chain[] = new UpdateManualDataJob($track);
        $chain[] = new UpdateCurrentDataJob($track);
        $chain[] = new UpdateEcTrack3DDemJob($track);
        $chain[] = new UpdateEcTrackSlopeValues($track);
        $chain[] = new UpdateModelWithGeometryTaxonomyWhere($track);
        $chain[] = new UpdateEcTrackGenerateElevationChartImage($track);
        $chain[] = new UpdateEcTrackAwsJob($track);
        $chain[] = new UpdateEcTrackElasticIndexJob($track);
        $chain[] = new UpdateTrackPBFInfoJob($track);
        $chain[] = new GeneratePBFByTrackJob($track);
        $chain[] = new UpdateEcTrackOrderRelatedPoi($track);

        Bus::chain($chain)
            ->catch(function (Throwable $e) {
                Log::error($e->getMessage());
            })->dispatch();
    }
}
