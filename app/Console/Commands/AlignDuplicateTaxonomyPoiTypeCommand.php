<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\TaxonomyPoiType;

class AlignDuplicateTaxonomyPoiTypeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:align_duplicate_taxonomy_poi_type
                        {duplicateId : The ID of the duplicate taxonomy to replace}
                        {mainId : The ID of the main taxonomy to consolidate into}
                        {--dry-run : Run the command in dry mode without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Align duplicate POI in taxonomy by merging IDs and removing unnecessary duplicates';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Ottieni gli ID dal command input
        $duplicateId = $this->argument('duplicateId');
        $mainId = $this->argument('mainId');

        // Recupera il record principale
        $mainRecord = TaxonomyPoiType::find($mainId);
        if (!$mainRecord) {
            $this->error("No taxonomy found with ID $mainId.");
            return 1;
        }

        // Ottieni il nome normalizzato del record principale
        $mainName = is_array($mainRecord->name) && isset($mainRecord->name['it']) ? $mainRecord->name['it'] : $mainRecord->name;
        $mainNameNormalized = $this->normalizeName($mainName);

        // Trova tutti i possibili duplicati (senza utilizzare regexp_replace)
        $potentialDuplicates = TaxonomyPoiType::where('id', '!=', $mainId)
            ->orWhere('id', $duplicateId) // Assicura che `duplicateId` sia incluso
            ->get();

        // Filtra i duplicati a livello di codice confrontando i nomi normalizzati
        $duplicates = $potentialDuplicates->filter(function ($record) use ($mainNameNormalized, $duplicateId) {
            $name = is_array($record->name) && isset($record->name['it']) ? $record->name['it'] : $record->name;
            return $this->normalizeName($name) === $mainNameNormalized || $record->id == $duplicateId;
        });

        // Conferma se esistono duplicati da aggiornare
        if ($duplicates->isEmpty()) {
            $this->info("No duplicates found with the normalized name '{$mainNameNormalized}'.");
            return 0;
        }

        // Modalità Dry-run
        if ($this->option('dry-run')) {
            $this->info("Dry run mode: no changes will be made.");

            // Conta gli ID `taxonomy_poi_typeable_id` che verrebbero aggiornati per ciascun duplicato
            foreach ($duplicates as $duplicate) {
                $relatedRowsCount = DB::table('taxonomy_poi_typeables')
                    ->where('taxonomy_poi_type_id', $duplicate->id)
                    ->count();

                // Recupera il nome e l'identificatore del duplicate
                $name = is_array($duplicate->name) && isset($duplicate->name['it']) ? $duplicate->name['it'] : $duplicate->name;
                $identifier = $duplicate->identifier;

                $this->info("Would update taxonomy_poi_type_id {$duplicate->id} (Name: {$name}, Identifier: {$identifier}) to $mainId for $relatedRowsCount entries.");
            }

            // Lista degli ID duplicati che verrebbero eliminati (incluso `duplicateId`)
            $this->info("The following taxonomy_poi_type IDs would be deleted:");
            foreach ($duplicates as $duplicate) {
                $name = is_array($duplicate->name) && isset($duplicate->name['it']) ? $duplicate->name['it'] : $duplicate->name;
                $identifier = $duplicate->identifier;
                $this->info(" - ID: {$duplicate->id} (Name: {$name}, Identifier: {$identifier})");
            }

            return 0;
        }

        // Disabilita i trigger
        DB::statement('ALTER TABLE taxonomy_poi_typeables DISABLE TRIGGER ALL;');

        // Aggiorna tutte le relazioni dal duplicato al principale
        foreach ($duplicates as $duplicate) {
            $updatedRows = DB::table('taxonomy_poi_typeables')
                ->where('taxonomy_poi_type_id', $duplicate->id)
                ->update(['taxonomy_poi_type_id' => $mainId]);
            $this->info("Updated $updatedRows rows from taxonomy_poi_type_id {$duplicate->id} to $mainId.");
        }

        // Riabilita i trigger
        DB::statement('ALTER TABLE taxonomy_poi_typeables ENABLE TRIGGER ALL;');

        // Elimina i record duplicati (incluso `duplicateId`)
        foreach ($duplicates as $duplicate) {
            $duplicate->delete();
            $this->info("Deleted duplicate taxonomy with ID {$duplicate->id}.");
        }

        $this->info("Successfully completed alignment of duplicate POIs for '{$mainNameNormalized}'.");

        return 0;
    }

    /**
     * Normalizza un nome rimuovendo numeri e mettendo tutto in minuscolo
     *
     * @param string $name
     * @return string
     */
    protected function normalizeName($name)
    {
        $name = strtolower($name);       // Converti in minuscolo
        $name = preg_replace('/\d/', '', $name); // Rimuovi i numeri
        return trim($name);
    }
}
