<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportFiscalCodeFromSentieroItalia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:import_fiscal_code_from_sentiero_italia
        {--csvUri=}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all the fiscal code from a csv generated from the Sentiero Italia website.
        By default uses an export file located in /import/sentieroItaliaFiscalCodes.csv in the local disk';

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
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        Log::channel('stdout')->info('Starting fiscal code import');
        $csvUri = $this->option('csvUri');

        if (! isset($csvUri)) {
            $csvUri = '/import/sentieroItaliaFiscalCodes.csv';
        }

        if (! Storage::disk('local')->exists($csvUri)) {
            Log::channel('stdout')->error('Missing import file: '.Storage::disk('local')->path($csvUri));

            return 1;
        }

        $handle = Storage::disk('local')->readStream($csvUri);
        $headers = [];
        $count = 0;

        while ($csvLine = fgetcsv($handle, 1000, ',')) {
            if (count($headers) === 0) {
                $headers = array_flip($csvLine);
            } else {
                $email = $csvLine[$headers['Email']];
                $fiscalCode = $csvLine[$headers['Codice fiscale']];
                if ($fiscalCode !== 'tappasiitalia' && strlen($fiscalCode) === 16) {
                    $user = User::where('email', '=', $email)->first();
                    if ($user && ! $user->fiscal_code) {
                        $user->fiscal_code = strtoupper($fiscalCode);
                        $user->save();
                        $count++;
                    }
                }
            }
        }

        Log::channel('stdout')->info("Imported $count fiscal codes successfully");

        return 0;
    }
}
