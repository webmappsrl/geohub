<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\EcTrack;
use App\Models\TaxonomyTheme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddTaxonomyCamminiToSardegnaSentieriCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage:
     *  php artisan geohub:add_taxonomy_cammini_to_sardegna_sentieri {theme_id?} {--dry-run}
     *
     *  - theme_id : ID della tassonomia tema da associare (default: 251, \"Cammini\")
     *  - --dry-run : Esegui solo un monitoraggio senza applicare modifiche
     *                (l'app viene fissata di default a 32)
     *
     * @var string
     */
    protected $signature = 'geohub:add_taxonomy_cammini_to_sardegna_sentieri
                            {theme_id=251 : ID della tassonomia tema da associare}
                            {--dry-run : Esegui solo un monitoraggio senza applicare modifiche}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggiunge la tassonomia tema "Cammini" (ID 251) a tutte le EcTrack dell\'app 32 (Sardegna Sentieri) il cui name inizia con CMSB';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $themeId = (int) $this->argument('theme_id'); // Default 251 da signature
        $dryRun = (bool) $this->option('dry-run');

        // App di default: 32 (Sardegna Sentieri)
        $appId = 32;

        /** @var App|null $app */
        $app = App::find($appId);
        if (! $app) {
            $this->error("Nessuna app trovata con id={$appId}");

            return 1;
        }

        /** @var TaxonomyTheme|null $theme */
        $theme = TaxonomyTheme::find($themeId);
        if (! $theme) {
            $this->error("Nessun tema trovato con id={$themeId}");

            return 1;
        }

        // Recupero le tracce dell'app tramite i layer, come in altri comandi
        $tracksFromLayer = $app->getTracksFromLayer();
        if (count($tracksFromLayer) === 0) {
            $this->info("Nessuna EcTrack trovata per l'app {$app->name} (ID:{$app->id}) tramite i layer.");

            return 0;
        }

        // Prendo gli ID delle tracce e filtro per name che inizia con 'CMSB'
        // Il campo name è JSON translatable, quindi uso il cast JSONB per PostgreSQL
        $trackIds = array_keys($tracksFromLayer);
        $locale = app()->getLocale();

        $query = EcTrack::whereIn('id', $trackIds)
            ->whereRaw("CAST(name AS JSONB)->>'{$locale}' LIKE ?", ['%CMSB%']);

        $total = $query->count();
        if ($total === 0) {
            $this->info("Nessuna EcTrack trovata nell'app {$app->id} con name che inizia per 'CMSB'.");

            return 0;
        }

        $this->info("Trovate {$total} EcTrack nell'app {$app->id} con name che inizia per 'CMSB'.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $alreadyHad = 0;

        $query->chunkById(100, function ($tracks) use ($theme, $dryRun, &$updated, &$alreadyHad, $bar) {
            /** @var EcTrack $track */
            foreach ($tracks as $track) {
                // Evita di duplicare l'associazione se già presente
                if (! $track->taxonomyThemes()->where('taxonomy_theme_id', $theme->id)->exists()) {
                    if (! $dryRun) {
                        $track->taxonomyThemes()->attach($theme->id);
                    }
                    $updated++;
                } else {
                    $alreadyHad++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info("Tracce che avrebbero ricevuto/che hanno ricevuto il tema: {$updated}.");
        $this->info("Tracce che avevano già il tema: {$alreadyHad}.");

        if ($dryRun) {
            $this->info('Modalità monitoraggio attiva (--dry-run): nessuna modifica è stata applicata.');
        } else {
            $this->info('Associazione completata.');
        }

        return 0;
    }
}
