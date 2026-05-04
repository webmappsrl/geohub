<?php

namespace App\Console\Commands;

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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;
use ZipArchive;

/**
 * Applica geometrie REER (KMZ ufficiale) alle sole tracce GeoHub marcate
 * "presente da aggiornare" nel workbook prodotto da geohub:compare-reer (foglio Tracce).
 */
class ApplyReerUpdatesFromWorkbookCommand extends Command
{
    private const STATUS_UPDATE = 'presente da aggiornare';

    protected $signature = 'geohub:apply-reer-from-workbook
        {workbook : Percorso assoluto al file .xlsx (es. export da Google Sheets)}
        {--sheet=Tracce : Nome del foglio con colonne ID_GEOHUB, REER_CHECK, REER_KMZ}
        {--dry-run : Elenca le righe da aggiornare senza modificare il database}
        {--limit=0 : Elabora al massimo N tracce (0 = tutte)}
        {--timeout=120 : Timeout HTTP in secondi per scaricare ogni KMZ}
    ';

    protected $description = 'Allinea la geometria delle tracce GeoHub al REER (KMZ) solo per righe "presente da aggiornare" nel workbook';

    public function handle(): int
    {
        $path = (string) $this->argument('workbook');
        $sheetName = (string) $this->option('sheet');
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $timeout = max(5, (int) $this->option('timeout'));

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
        $colIndex = $this->mapHeaderRow($header);
        if ($colIndex['id'] === null || $colIndex['check'] === null || $colIndex['kmz'] === null) {
            $this->error('Intestazioni attese: ID_GEOHUB, REER_CHECK, REER_KMZ (opzionale LINK_EDIT_GEOHUB). Trovato: '.json_encode($header));

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
            $kmz = $colIndex['kmz'] !== null ? trim((string) ($row[$colIndex['kmz']] ?? '')) : '';
            if ($kmz === '' || ! filter_var($kmz, FILTER_VALIDATE_URL)) {
                $this->warn("Traccia {$trackId}: REER_KMZ assente o URL non valido, skip.");
                continue;
            }
            $candidates[] = ['track_id' => $trackId, 'kmz' => $kmz];
        }

        $total = count($candidates);
        if ($limit > 0) {
            $candidates = array_slice($candidates, 0, $limit);
        }

        $this->info('Righe "presente da aggiornare" con KMZ valido: '.$total.' (elaboro: '.count($candidates).')');

        if ($candidates === []) {
            return 0;
        }

        if ($dryRun) {
            foreach ($candidates as $c) {
                $this->line("[dry-run] track_id={$c['track_id']} kmz={$c['kmz']}");
            }
            $this->info('Nessuna modifica (dry-run).');

            return 0;
        }

        $ok = 0;
        $fail = 0;
        foreach ($candidates as $c) {
            $trackId = $c['track_id'];
            $kmzUrl = $c['kmz'];
            try {
                $payload = $this->fetchKmlOrRawXml($kmzUrl, $timeout);
                if ($payload === null || $payload === '') {
                    $this->error("Traccia {$trackId}: download KMZ/KML fallito o archivio non valido.");
                    $fail++;

                    continue;
                }
                $track = EcTrack::query()->find($trackId);
                if (! $track) {
                    $this->error("Traccia {$trackId}: non trovata.");
                    $fail++;

                    continue;
                }
                $geometry = $track->fileToGeometry($payload);
                if ($geometry === null) {
                    $this->error("Traccia {$trackId}: impossibile derivare la geometria dal file REER.");
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
            } catch (Throwable $e) {
                Log::error('geohub:apply-reer-from-workbook track '.$trackId.': '.$e->getMessage(), ['e' => $e]);
                $this->error("Traccia {$trackId}: ".$e->getMessage());
                $fail++;
            }
        }

        $this->info("Completato. OK={$ok}, errori={$fail}");

        return $fail > 0 ? 1 : 0;
    }

    /**
     * @param  list<mixed>  $headerRow
     * @return array{id: int|null, check: int|null, kmz: int|null}
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
            'check' => $map['reer_check'] ?? null,
            'kmz' => $map['reer_kmz'] ?? null,
        ];
    }

    private function normalizeStatus(mixed $value): string
    {
        $s = strtolower(trim((string) $value));

        return match ($s) {
            'presente da aggiornare', 'presente_da_aggiornare' => self::STATUS_UPDATE,
            default => $s,
        };
    }

    private function parseTrackId(mixed $value): ?int
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
        if (str_starts_with($trim, '<?xml') || str_starts_with($trim, '<kml')) {
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
