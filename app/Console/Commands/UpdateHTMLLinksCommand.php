<?php

namespace App\Console\Commands;

use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\OutSourceFeature;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateHTMLLinksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:update-html-links
                            {type : Set the Ec type (track, poi)}
                            {author : Set the author that must be assigned to EcFeature crested, use email or ID}
                            {--force : Forces the update process even if the destination is not empty}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command findes the links in descprition of the EcFeatures and updates them with tag <a>';

    protected $type;
    protected $author;
    protected $author_id;
    protected $force;

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

        if ($this->option('force')) {
            $this->force = true;
        }

        $this->checkParameters();

        $list = $this->getList();
    }

    public function sync($feature)
    {

        if (!empty($feature->description)) {
            $description = $feature->getTranslations('description');

            foreach ($description as $lang => $text) {
                $description[$lang] = preg_replace_callback(
                    '/(?<!href=")https?:\/\/\S+/i',
                    function ($matches) {
                        $url = $matches[0];
                        $domain = parse_url($url, PHP_URL_HOST);
                        return '<a href="' . $url . '" target="_blank">' . $domain . '</a>';
                    },
                    $text
                );
            }

            $feature->setTranslations('description', $description);
            $feature->save();
            return true;
        }
        return false;
    }

    public function getList()
    {
        $features = '';

        if ($this->type == 'poi') {
            $features = EcPoi::where('user_id', $this->author_id, )->get();
        }
        if ($this->type == 'track') {
            $features = EcTrack::where('user_id', $this->author_id, )->get();
        }

        if ($features) {
            $count = 1;
            foreach ($features as $feature) {
                $res = $this->sync($feature);
                if ($res) {
                    Log::info('Description of Feature with ID: ' . $feature->id . ' saved successfully - ' . $count . ' out of -> ' . count($features));
                    $this->info('Description of Feature with ID: ' . $feature->id . ' saved successfully - ' . $count . ' out of -> ' . count($features));
                } else {
                    Log::info('Description of Feature with ID: ' . $feature->id . ' NOT saved - ' . $count . ' out of -> ' . count($features));
                    $this->error('Description of Feature with ID: ' . $feature->id . ' NOT saved - ' . $count . ' out of -> ' . count($features));
                }
                $count++;
            }
            // return $features->pluck('id')->toArray();
        } else {
            return 0;
        }
    }

    public function checkParameters()
    {
        // Check the author
        Log::info('Checking paramtere AUTHOR');
        if (is_numeric($this->author)) {
            try {
                $user = User::find(intval($this->author));
                $this->author_id = $user->id;
            } catch (Exception $e) {
                throw new Exception('No User found with this ID ' . $this->author);
            }
        } else {
            try {
                $user = User::where('email', strtolower($this->author))->first();

                $this->author_id = $user->id;
            } catch (Exception $e) {
                throw new Exception('No User found with this email ' . $this->author);
            }
        }

        // Check the type
        Log::info('Checking paramtere TYPE');
        if (
            strtolower($this->type) == 'track' ||
            strtolower($this->type) == 'poi'
        ) {
            $this->type = strtolower($this->type);
        } else {
            throw new Exception('The value of parameter type: ' . $this->type . ' is not currect');
        }
    }
}
