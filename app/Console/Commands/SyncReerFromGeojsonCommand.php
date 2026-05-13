<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Esegue geohub:compare-reer e, se richiesto, geohub:apply-reer-from-workbook
 * sul workbook appena generato (solo righe "presente da aggiornare").
 */
class SyncReerFromGeojsonCommand extends Command
{
    protected $signature = 'geohub:sync-reer-from-geojson
        {geojson : Percorso al GeoJSON REER (come in compare-reer)}
        {--scope=user : Perimetro tracce: user | app}
        {--email=pec@webmapp.it : Email utente (con scope=user)}
        {--app-id= : ID app GeoHub (con scope=app)}
        {--dwithin-m=50 : Tolleranza ST_DWithin in metri}
        {--hausdorff-m=20 : Soglia Hausdorff per "presente e aggiornato"}
        {--topn=10 : Candidati REER (KNN) per Hausdorff}
        {--match-chunk=40 : Tracce per batch nel matching}
        {--out-xlsx=report_reer_geohub.xlsx : Workbook in storage/app}
        {--out-csv= : Opzionale CSV in storage/app}
        {--out-md= : Opzionale Markdown in storage/app}
        {--base-edit-url=https://geohub.webmapp.it : Base URL LINK_EDIT_GEOHUB}
        {--sheet=Tracce : Foglio tracce nel workbook}
        {--compare-only : Solo confronto (nessun aggiornamento DB)}
        {--dry-run : Dopo il confronto, elenca l\'apply senza modificare il DB}
        {--limit=0 : Max tracce da aggiornare (0 = tutte)}
        {--timeout=120 : Timeout HTTP per KMZ se manca geometria nel GeoJSON}
        {--post-apply-output= : Workbook post-apply (default con timestamp)}
        {--no-post-export : Non scrivere Excel post-apply}
    ';

    protected $description = 'Confronto REER (GeoJSON) vs GeoHub, poi aggiorna le tracce "presente da aggiornare"';

    public function handle(): int
    {
        $geojsonPath = $this->resolvePath((string) $this->argument('geojson'));
        if (! is_readable($geojsonPath)) {
            $this->error("File GeoJSON non leggibile: {$geojsonPath}");

            return 1;
        }

        $compareParams = [
            'geojson' => $geojsonPath,
            '--scope' => (string) $this->option('scope'),
            '--email' => (string) $this->option('email'),
            '--dwithin-m' => (string) $this->option('dwithin-m'),
            '--hausdorff-m' => (string) $this->option('hausdorff-m'),
            '--topn' => (string) $this->option('topn'),
            '--match-chunk' => (string) $this->option('match-chunk'),
            '--out-xlsx' => (string) $this->option('out-xlsx'),
            '--base-edit-url' => rtrim((string) $this->option('base-edit-url'), '/'),
        ];
        $appId = $this->option('app-id');
        if ($appId !== null && (string) $appId !== '') {
            $compareParams['--app-id'] = (string) $appId;
        }
        $outCsv = trim((string) $this->option('out-csv'));
        if ($outCsv !== '') {
            $compareParams['--out-csv'] = $outCsv;
        }
        $outMd = trim((string) $this->option('out-md'));
        if ($outMd !== '') {
            $compareParams['--out-md'] = $outMd;
        }

        $this->info('=== Passo 1/2: confronto REER vs GeoHub ===');
        $compareExit = Artisan::call('geohub:compare-reer', $compareParams, $this->output);
        if ($compareExit !== 0) {
            $this->error('Confronto fallito: interrompo (nessun aggiornamento).');

            return $compareExit;
        }

        if ((bool) $this->option('compare-only')) {
            $this->info('=== --compare-only: confronto completato, skip apply ===');

            return 0;
        }

        $workbookRel = ltrim((string) $this->option('out-xlsx'), '/');
        $workbookAbs = storage_path('app/'.$workbookRel);

        if (! is_readable($workbookAbs)) {
            $this->error("Workbook non trovato dopo il confronto: {$workbookAbs}");

            return 1;
        }

        $applyParams = [
            'workbook' => $workbookAbs,
            '--geojson' => $geojsonPath,
            '--sheet' => (string) $this->option('sheet'),
            '--limit' => (string) $this->option('limit'),
            '--timeout' => (string) $this->option('timeout'),
        ];
        if ((bool) $this->option('dry-run')) {
            $applyParams['--dry-run'] = true;
        }
        if ((bool) $this->option('no-post-export')) {
            $applyParams['--no-post-export'] = true;
        }
        $postOut = $this->option('post-apply-output');
        if (is_string($postOut) && $postOut !== '') {
            $applyParams['--post-apply-output'] = $this->resolvePath($postOut);
        }

        $this->info('=== Passo 2/2: aggiorna tracce "presente da aggiornare" (GeoJSON + workbook) ===');
        $applyExit = Artisan::call('geohub:apply-reer-from-workbook', $applyParams, $this->output);

        return $applyExit;
    }

    private function resolvePath(string $path): string
    {
        if ($path !== '' && $path[0] === DIRECTORY_SEPARATOR) {
            return $path;
        }

        return base_path($path);
    }
}
