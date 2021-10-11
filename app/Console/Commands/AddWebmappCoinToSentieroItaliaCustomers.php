<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AddWebmappCoinToSentieroItaliaCustomers extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:add_webmapp_coin_to_sentiero_italia_customers
        {--csvUri=}
    ';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add the Webmapp Coins to the Mappa Sentiero Italia buyers.
        By default uses an export file located in /import/sentieroItaliaOrders.csv in the local disk';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     *
     * @throws FileNotFoundException
     */
    public function handle(): int {
        Log::channel('stdout')->info("Starting orders import");
        $csvUri = $this->option('csvUri');

        if (!isset($csvUri))
            $csvUri = '/import/sentieroItaliaOrders.csv';

        if (!Storage::disk('local')->exists($csvUri)) {
            Log::channel('stdout')->error("Missing import file: " . Storage::disk('local')->path($csvUri));

            return 1;
        }

        $buyers = [];

        $handle = Storage::disk('local')->readStream($csvUri);
        $headers = [];
        $res = DB::table('sync')->where('type', '=', 'sentiero_italia_orders_sync')->first();
        $oldLastDate = isset($res) && $res->last_item_date ? strtotime($res->last_item_date) : null;
        $newLastDate = $oldLastDate;

        while ($csvLine = fgetcsv($handle, 1000, ",")) {
            if (count($headers) === 0)
                $headers = array_flip($csvLine);
            else {
                $email = $csvLine[$headers["Email utente"]];
                $price = floatval($csvLine[$headers["Totale"]]);
                $state = $csvLine[$headers["Stato"]];
                $date = $csvLine[$headers["Data"]] ? strtotime($csvLine[$headers["Data"]]) : null;

                if ($state === "completed" && $price >= 0
                    && ($date > $oldLastDate || !$oldLastDate)) {
                    if (!array_key_exists($email, $buyers))
                        $buyers[$email] = 0;

                    $buyers[$email] += $price;

                    if (!$newLastDate || $newLastDate < $date)
                        $newLastDate = $date;
                }
            }
        }

        foreach ($buyers as $email => $price) {
            $user = User::where('email', '=', $email)->first();
            if ($user) {
                $forties = intval($price / 40);
                $twenties = intval(($price % 40) / 20);
                $tens = intval((($price % 40) % 20) / 10);
                $ones = (($price % 40) % 20) % 10;
                $user->balance += $forties * 1000 + $twenties * 500 + $tens * 200 + $ones * 10;
                $user->save();
            }
        }

        if (is_null($res)) {
            DB::table('sync')->insert([
                'type' => 'sentiero_italia_orders_sync',
                'last_item_date' => Carbon::parse($newLastDate),
                'last_update' => now()
            ]);
        } else {
            DB::table('sync')->update([
                'id' => $res->id,
                'type' => 'sentiero_italia_orders_sync',
                'last_item_date' => Carbon::parse($newLastDate),
                'last_update' => now()
            ]);
        }

        Log::channel('stdout')->info("Imported all orders successfully");

        return 0;
    }
}
