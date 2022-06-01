<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OutSourceTaxonomyMappingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:out_source_taxonomy_mapping {endpoint : url to the resource (e.g. https://stelvio.wp.webmapp.it)} {provider : WP, StorageCSV} {--activity : add this flag to map activity taxonomy} {--poi_type : add this flag to map webmapp_category/poi_type taxonomy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the taxonomies from external resource and creates a mapping file';

    protected $type;
    protected $endpoint;
    protected $activity;
    protected $poi_type;
    protected $content;

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
        $this->content = "<?php \n";

        $this->endpoint = $this->argument('endpoint');
        $this->activity = $this->option('activity');
        $this->poi_type = $this->option('poi_type');
        $provider = $this->argument('provider');

        switch (strtolower($provider)) {
            case 'wp':
                return $this->importerWP();
                break;
            
            case 'storagecsv':
                return $this->importerStorageCSV();
                break;
                    
            default:
                return [];
                break;
        }       
    }


    private function importerWP(){
        if ($this->poi_type) {
            $this->importerWPPoiType();
        }
        if ($this->activity) {
            $this->importerWPActivity();
        }
        if ($this->activity == false && $this->poi_type == false) {
            $this->importerWPPoiType();
            $this->importerWPActivity();
        }

        $this->createMappingFile();
        
    }

    private function importerWPPoiType(){
        $this->content .= 'poi';
    }

    private function importerWPActivity(){
        $this->content .= 'activity';
    }
    
    private function createMappingFile(){
        $path = parse_url($this->endpoint);
        $file_name = str_replace('.','-',$path['host']);
        Storage::disk('mapping')->put($file_name.'.php', $this->content);
    }
}
