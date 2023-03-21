<?php

namespace App\Console\Commands;

use App\Traits\ImporterAndSyncTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OutSourceTaxonomyMappingCommand extends Command
{
    use ImporterAndSyncTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:out_source_taxonomy_mapping 
                            {endpoint : url to the resource (e.g. https://stelvio.wp.webmapp.it)} 
                            {provider : WP, StorageCSV, sentierisardegna} 
                            {--activity : add this flag to map activity taxonomy} 
                            {--theme : add this flag to map theme taxonomy} 
                            {--poi_type : add this flag to map webmapp_category/poi_type taxonomy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the taxonomies from external resource and creates a mapping file';

    protected $type;
    protected $endpoint;
    protected $activity;
    protected $theme;
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
        if(app()->environment('production')){
            $this->error('Sorry, Alessio said you can not run this in production! :-P');
            return;
        }

        $this->endpoint = $this->argument('endpoint');
        $this->activity = $this->option('activity');
        $this->theme = $this->option('theme');
        $this->poi_type = $this->option('poi_type');
        $provider = $this->argument('provider');

        switch (strtolower($provider)) {
            case 'wp':
                return $this->importerWP();
                break;
            
            case 'storagecsv':
                return $this->importerStorageCSV();
                break;
            
            case 'sentierisardegna':
                return $this->importerSentieriSardegna();
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
        if ($this->theme) {
            $this->importerWPTheme();
        }
        if ($this->activity == false && $this->poi_type == false && $this->theme == false) {
            $this->importerWPPoiType();
            $this->importerWPActivity();
            $this->importerWPTheme();
        }

        $this->createMappingFile();
    }


    private function importerSentieriSardegna(){
        if ($this->poi_type) {
            $this->importerSSPoiType();
        }
        if ($this->theme) {
            $this->importerSSTheme();
        }

        $this->createMappingFile();
    }

    private function importerWPPoiType(){
        $url = $this->endpoint.'/wp-json/wp/v2/webmapp_category?per_page=99';
        $WC = $this->curlRequest($url);
        $input = [];
        if ($WC) {
            foreach ($WC as $c) {
                if ($c['count'] > 0) {
                    Log::info('Start creating input poi_type '.$c['name'].' with external id: '.$c['id']);
                    $title = [];
                    if (!empty($c['wpml_current_locale']) && isset($c['wpml_current_locale'])) {
                        $title = [
                            explode('_',$c['wpml_current_locale'])[0] => $c['name'],
                        ];
                        $description = [
                            explode('_',$c['wpml_current_locale'])[0] => $c['description'],
                        ];
                        if(!empty($c['wpml_translations'])) {
                            foreach($c['wpml_translations'] as $lang){
                                $locale = explode('_',$lang['locale']);
                                $title[$locale[0]] = $lang['name']; 
                                $cat_decode = $this->curlRequest($lang['source']);
                                $description[$locale[0]] = $cat_decode['description']; 
                            }
                        }
                        $input[$c['id']] = [
                            'source_title' => $title,
                            'source_description' => $description,
                            'geohub_identifier' => '',
                        ];
                    } else {
                        $title = [
                            'it' => $c['name'],
                        ];
                        $description = [
                            'it' => $c['description'],
                        ];
                        $input[$c['id']] = [
                            'source_title' => $title,
                            'source_description' => $description,
                            'geohub_identifier' => '',
                        ];
                    }
                }
            }
        }
        $this->content["poi_type"] = $input;
    }

    private function importerWPActivity(){
        $url = $this->endpoint.'/wp-json/wp/v2/activity?per_page=99';
        $WC = $this->curlRequest($url);
        $input = [];
        if ($WC) {
            foreach ($WC as $c) {
                if ($c['count'] > 0) {
                    Log::info('Start creating input poi_type '.$c['name'].' with external id: '.$c['id']);
                    if (!empty($c['wpml_current_locale']) && isset($c['wpml_current_locale'])) {
                        $title = [];
                        $title = [
                            explode('_',$c['wpml_current_locale'])[0] => $c['name'],
                        ];
                        $description = [
                            explode('_',$c['wpml_current_locale'])[0] => $c['description'],
                        ];
                        if(!empty($c['wpml_translations'])) {
                            foreach($c['wpml_translations'] as $lang){
                                $locale = explode('_',$lang['locale']);
                                $title[$locale[0]] = $lang['name']; 
                                $cat_decode = $this->curlRequest($lang['source']);
                                $description[$locale[0]] = $cat_decode['description']; 
                            }
                        }
                        $input[$c['id']] = [
                            'source_title' => $title,
                            'source_description' => $description,
                            'geohub_identifier' => '',
                        ];
                    } else {
                        $title = [
                            'it' => $c['name'],
                        ];
                        $description = [
                            'it' => $c['description'],
                        ];
                        $input[$c['id']] = [
                            'source_title' => $title,
                            'source_description' => $description,
                            'geohub_identifier' => '',
                        ];
                    }
                }
            }
        }
        $this->content["activity"] = $input;
    }

    private function importerWPTheme(){
        $url = $this->endpoint.'/wp-json/wp/v2/theme?per_page=99';
        $WC = $this->curlRequest($url);
        $input = [];
        if ($WC) {
            foreach ($WC as $c) {
                if ($c['count'] > 0) {
                    Log::info('Start creating input theme '.$c['name'].' with external id: '.$c['id']);
                    if (!empty($c['wpml_current_locale']) && isset($c['wpml_current_locale'])) {
                        $title = [];
                        $title = [
                            explode('_',$c['wpml_current_locale'])[0] => $c['name'],
                        ];
                        $description = [
                            explode('_',$c['wpml_current_locale'])[0] => $c['description'],
                        ];
                        if(!empty($c['wpml_translations'])) {
                            foreach($c['wpml_translations'] as $lang){
                                $locale = explode('_',$lang['locale']);
                                $title[$locale[0]] = $lang['name']; 
                                $cat_decode = $this->curlRequest($lang['source']);
                                $description[$locale[0]] = $cat_decode['description']; 
                            }
                        }
                        $input[$c['id']] = [
                            'source_title' => $title,
                            'source_description' => $description,
                            'geohub_identifier' => '',
                        ];
                    } else {
                        $title = [
                            'it' => $c['name'],
                        ];
                        $description = [
                            'it' => $c['description'],
                        ];
                        $input[$c['id']] = [
                            'source_title' => $title,
                            'source_description' => $description,
                            'geohub_identifier' => '',
                        ];
                    }
                }
            }
        }
        $this->content["theme"] = $input;
    }
    
    private function importerSSPoiType(){
        $response = [];
        $response['accessibilità'] = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get('https://sentieri.netseven.work/ss/tassonomia/accessibilit_?_format=json')->json();
        $response['servizi'] = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get('https://sentieri.netseven.work/ss/tassonomia/servizi?_format=json')->json();
        $response['tipologia_poi'] = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get('https://sentieri.netseven.work/ss/tassonomia/tipologia_poi?_format=json')->json();
        $response['zona_geografica'] = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get('https://sentieri.netseven.work/ss/tassonomia/zona_geografica?_format=json')->json();
        $response['tipo_ente_istituzione_societa'] = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get('https://sentieri.netseven.work/ss/tassonomia/tipo_ente_istituzione_societa?_format=json')->json();
        $response['tipo_di_fondo'] = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get('https://sentieri.netseven.work/ss/tassonomia/tipo_di_fondo?_format=json')->json();

        $input = [];
        if ($response) {
            foreach ($response as $type => $taxonomies) {
                foreach ($taxonomies as $id => $tax) {
                    $identifier = 'sardegnas:'.$type.':'.str_replace(' ','-',$tax['name']['it']);
                    $input[$id] = [
                        'source_title' => $tax['name'],
                        'source_description' => $tax['description'] ?? '',
                        'geohub_identifier' => $identifier
                    ];
                }
            }
        }
        $this->content["poi_type"] = $input;
    }

    private function importerSSTheme(){
        $response = [];
        $response['tipologia_sentieri'] = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get('https://sentieri.netseven.work/ss/tassonomia/tipologia_sentieri?_format=json')->json();
        $response['categorie_fruibilita_sentieri'] = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get('https://sentieri.netseven.work/ss/tassonomia/categorie_fruibilita_sentieri?_format=json')->json();
        $response['tipo_ente_istituzione_societa'] = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get('https://sentieri.netseven.work/ss/tassonomia/tipo_ente_istituzione_societa?_format=json')->json();
        $response['stato_di_validazione'] = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get('https://sentieri.netseven.work/ss/tassonomia/stato_di_validazione?_format=json')->json();
        $response['tipo_di_fondo'] = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get('https://sentieri.netseven.work/ss/tassonomia/tipo_di_fondo?_format=json')->json();
        $response['zona_geografica'] = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get('https://sentieri.netseven.work/ss/tassonomia/zona_geografica?_format=json')->json();

        $input = [];
        if ($response) {
            foreach ($response as $type => $taxonomies) {
                foreach ($taxonomies as $id => $tax) {
                    $identifier = 'sardegnas:'.$type.':'.str_replace(' ','-',$tax['name']['it']);
                    $input[$id] = [
                        'source_title' => $tax['name'],
                        'source_description' => $tax['description'] ?? '',
                        'geohub_identifier' => $identifier
                    ];
                }
            }
        }
        $this->content["theme"] = $input;
    }

    private function createMappingFile(){
        $path = parse_url($this->endpoint);
        $file_name = str_replace('.','-',$path['host']);
        Log::info('Creating mapping file: '.$file_name);
        $p = Storage::disk('mapping')->put($file_name.'.json', json_encode($this->content,JSON_PRETTY_PRINT));
        Log::info('Finished creating file: '.$file_name);
    }
}
