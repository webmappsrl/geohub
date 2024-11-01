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
    protected $description = 'Align duplicate taxonomy Poi Type by merging IDs and removing unnecessary duplicates';

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

        // Verifica se il duplicateId esiste
        $duplicateRecord = TaxonomyPoiType::find($duplicateId);
        if (!$duplicateRecord) {
            $this->info("No duplicate taxonomy found with ID $duplicateId. Nothing to do.");
            return 0;
        }

        // Ottieni il nome e l'identificatore del record principale
        $mainName = is_array($mainRecord->name) && isset($mainRecord->name['it']) ? $mainRecord->name['it'] : $mainRecord->name;
        $mainIdentifier = $mainRecord->identifier;
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

        // Stampa l'anteprima delle modifiche
        $this->info("Running in " . ($this->option('dry-run') ? "dry run" : "real") . " mode.\n");
        $this->info("The following updates would be made:");
        foreach ($duplicates as $duplicate) {
            $relatedRowsCount = DB::table('taxonomy_poi_typeables')
                ->where('taxonomy_poi_type_id', $duplicate->id)
                ->count();

            $name = is_array($duplicate->name) && isset($duplicate->name['it']) ? $duplicate->name['it'] : $duplicate->name;
            $identifier = $duplicate->identifier;

            $this->info(" - ID: {$duplicate->id} (Name: {$name}, Identifier: {$identifier})");
            $this->info("   => to $mainId (Name: {$mainName}, Identifier: {$mainIdentifier}) for $relatedRowsCount entries.");
        }

        $this->info("\nThe following taxonomy_poi_type IDs would be deleted:");
        foreach ($duplicates as $duplicate) {
            $name = is_array($duplicate->name) && isset($duplicate->name['it']) ? $duplicate->name['it'] : $duplicate->name;
            $identifier = $duplicate->identifier;
            $this->info(" - ID: {$duplicate->id} (Name: {$name}, Identifier: {$identifier})");
        }

        // Se è in modalità dry-run, termina qui senza fare modifiche
        if ($this->option('dry-run')) {
            return 0;
        }

        // Conferma per procedere con le modifiche in real mode
        if (!$this->confirm("Do you want to proceed with these changes?")) {
            $this->info("Operation cancelled.");
            return 0;
        }

        // Stampa le informazioni strutturate nuovamente durante l'esecuzione delle modifiche
        $this->info("\nApplying the following updates:");
        foreach ($duplicates as $duplicate) {
            $relatedRowsCount = DB::table('taxonomy_poi_typeables')
                ->where('taxonomy_poi_type_id', $duplicate->id)
                ->update(['taxonomy_poi_type_id' => $mainId]);

            $name = is_array($duplicate->name) && isset($duplicate->name['it']) ? $duplicate->name['it'] : $duplicate->name;
            $identifier = $duplicate->identifier;

            $this->info(" - Updated {$relatedRowsCount} rows from ID: {$duplicate->id} (Name: {$name}, Identifier: {$identifier})");
            $this->info("   => to $mainId (Name: {$mainName}, Identifier: {$mainIdentifier})");
        }

        $this->info("\nDeleting the following duplicate taxonomy_poi_type IDs:");
        foreach ($duplicates as $duplicate) {
            $name = is_array($duplicate->name) && isset($duplicate->name['it']) ? $duplicate->name['it'] : $duplicate->name;
            $identifier = $duplicate->identifier;
            $duplicate->delete();
            $this->info(" - Deleted ID: {$duplicate->id} (Name: {$name}, Identifier: {$identifier})");
        }

        $this->info("Successfully completed");

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
