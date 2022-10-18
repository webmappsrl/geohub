<?php

namespace App\Console\Commands;

use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\OutSourceFeature;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncECTagsFromOSFCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:sync-ec-tags-from-osf
                            {type : Set the Ec type (track, poi)}
                            {author : Set the author that must be assigned to EcFeature crested, use email or ID}
                            {--tag= : Set the name of the input that should be syncronized (es. description)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command copies the value of the given input from OSF to the given EC resource';


    protected $type;
    protected $author;
    protected $author_id;
    protected $tag;

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
        $this->type = $this->argument('type');
        $this->author = $this->argument('author');
        if ($this->option('tag'))
            $this->tag = $this->option('tag');

        $this->checkParameters();
        
        $list = $this->getList();
    }

    public function sync($feature){
        $out_source = OutSourceFeature::find($feature->out_source_feature_id);
        if ($this->tag != 'all') {
            if ($out_source) {
                if (empty($feature->description)) {
                    $feature->description = $out_source->tags[$this->tag];
                    $feature->save();
                    return true;
                }
            }
        } else {
            if ($out_source) {
                if (empty($feature->description)) {
                    if (isset($out_source->tags['description']))
                        $feature->description = $out_source->tags['description'];
                }
                if (empty($feature->addr_locality)) {
                    if (isset($out_source->tags['addr_city']))
                        $feature->addr_locality = $out_source->tags['addr_city'];
                }
                if (empty($feature->addr_housenumber)) {
                    if (isset($out_source->tags['addr_housenumber']))
                        $feature->addr_housenumber = $out_source->tags['addr_housenumber'];
                }
                if (empty($feature->addr_street)) {
                    if (isset($out_source->tags['addr_street']))
                        $feature->addr_street = $out_source->tags['addr_street'];
                }
                if (empty($feature->addr_postcode)) {
                    if (isset($out_source->tags['addr_postcode']))
                        $feature->addr_postcode = $out_source->tags['addr_postcode'];
                }
                if (empty($feature->opening_hours)) {
                    if (isset($out_source->tags['opening_hours']))
                        $feature->opening_hours = $out_source->tags['opening_hours'];
                }
                if (empty($feature->contact_phone)) {
                    if (isset($out_source->tags['contact_phone']))
                        $feature->contact_phone = $out_source->tags['contact_phone'];
                }
                if (empty($feature->contact_email)) {
                    if (isset($out_source->tags['contact_email']))
                        $feature->contact_email = $out_source->tags['contact_email'];
                }
                if (empty($feature->capacity)) {
                    if (isset($out_source->tags['capacity']))
                        $feature->capacity = $out_source->tags['capacity'];
                }
                if (empty($feature->stars)) {
                    if (isset($out_source->tags['stars']))
                        $feature->stars = $out_source->tags['stars'];
                }
                // if (empty($feature->related_url)) {
                //     $urls = [];
                //     foreach ($out_source->tags['related_url'] as $key => $val) {
                //         if ($val) {
                //             $urls[] = [$key => $val];
                //         }
                //     }
                //     $feature->related_url = implode(",",$urls);
                // }
                $feature->save();
                return true;
            }
        }
    }

    public function getList(){
        $features = '';

        if ($this->type == 'poi') {
            $features = EcPoi::where('user_id', $this->author_id,)->get();
        }
        if ($this->type == 'track') {
            $features = EcTrack::where('user_id', $this->author_id,)->get();
        }

        if ($features){
            $count = 1;
            foreach ($features as $feature) {
                $res = $this->sync($feature);
                if ($res) {
                    Log::info($this->tag .' of Feature with ID: '. $feature->id . ' saved successfully - ' .$count. ' out of -> ' . count($features) );
                } else {
                    Log::info($this->tag .' of Feature with ID: '. $feature->id . ' NOT saved - ' .$count. ' out of -> ' . count($features) );
                }
                $count ++;
            }
            // return $features->pluck('id')->toArray();
        } else {
            return 0;
        }
    }

    public function checkParameters(){
        // Check the author
        Log::info('Checking paramtere AUTHOR');
        if (is_numeric($this->author)) {
            try {
                $user = User::find(intval($this->author));
                $this->author_id = $user->id;
            } catch (Exception $e) {
                throw new Exception('No User found with this ID '. $this->author); 
            }
        } else {
            try {
                $user = User::where('email',strtolower($this->author))->first();
                
                $this->author_id = $user->id;
                
            } catch (Exception $e) {
                throw new Exception('No User found with this email '. $this->author); 
            }
        }

        // Check the type
        Log::info('Checking paramtere TYPE');
        if (strtolower($this->type) == 'track' ||
            strtolower($this->type) == 'poi'
            ) {
                $this->type = strtolower($this->type);
            } else {
                throw new Exception('The value of parameter type: '.$this->type.' is not currect'); 
            }
        
        
        // Check the type
        Log::info('Checking paramtere TAG');
        if ($this->tag) {
            if (strtolower($this->tag) == 'description' ||
                strtolower($this->tag) == 'excerpt'
                ) {
                    $this->tag = strtolower($this->tag);
                } else {
                    throw new Exception('The value of parameter tag: '.$this->tag.' is not currect'); 
                }
        } else {
            $this->tag = 'all';
        }
    }
}
