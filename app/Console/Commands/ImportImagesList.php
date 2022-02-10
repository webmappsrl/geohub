<?php

namespace App\Console\Commands;

use App\Models\EcMedia;
use App\Models\User;
use ErrorException;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ImportImagesList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:import_images 
                            {url : Url or path of the image zip to store in the server}
                            {user_id : the user to assaciate to new media}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use this command to import a zip file with images and create EcMedia';

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
     * @return array the ids of created media
     */
    public function handle()
    {
        $createdEcMedia = [];
        $allowedMimeTypes = ['jpeg', 'gif', 'png', 'bmp', 'svg', 'jpg'];

        $user_id = $this->argument('user_id');
        $user = User::find($user_id);
        if(empty($user)) {
            throw new Exception("User_id $user_id does NOT exist.");
        }
        Auth::login($user);

        $url = $this->argument('url');
        $urlPathinfo = pathinfo($url);
        if ($urlPathinfo['extension'] != 'zip') {
            return $this->error('Error, the selected file is not a ZIP file');
        } else {
            $tmpzip = (base_path() . '/storage/tmp/imported_images');
            $zip = new ZipArchive;
            if ($zip->open($url) === TRUE) {
                $zip->extractTo($tmpzip);
                $zip->close();
            } else {
                Log::info("cant open zip file $url");
            }
        }
        $this->info("file unzipped in $tmpzip");
        $fileList = scandir($tmpzip);
        unset($fileList[0], $fileList[1]);

        foreach ($fileList as $file) {
            if ($file == '__MACOSX') {
                continue;
            } elseif (is_dir($tmpzip . '/' . $file)) {

                $dirFileList = scandir($tmpzip . '/' . $file);
                unset($dirFileList[0], $dirFileList[1]);
                foreach ($dirFileList as $dirFile) {
                    if ($dirFile == '.DS_Store') {
                        $DsStoreFile = array_search('.DS_Store', $dirFileList);
                        unset($dirFileList[$DsStoreFile]);
                        unlink(base_path() . '/storage/tmp/imported_images/' . $file . '/.DS_Store');
                        continue;
                    }

                    $pathInfoFile = pathinfo($dirFile);
                    $contents = file_get_contents(base_path() . '/storage/tmp/imported_images/' . $file . '/' . $pathInfoFile['basename']);
                    if (in_array($pathInfoFile['extension'], $allowedMimeTypes)) {

                        Log::info('Waiting 2 seconds...');
                        sleep(2);

                        $newEcmedia = EcMedia::create(['name' => $pathInfoFile['filename'], 'url' => '']);
                        Storage::disk('public')->put('ec_media/' . $newEcmedia->id, $contents);
                        $newEcmedia->url = 'ec_media/' . $newEcmedia->id;
                        $newEcmedia->save();
                        $this->info("Created EcMedia with id : $newEcmedia->id");
                        $createdEcMedia[] = $newEcmedia->id;
                        unlink(base_path() . '/storage/tmp/imported_images/' . $file . '/' . $pathInfoFile['basename']);
                        try {
                            rmdir(base_path() . '/storage/tmp/imported_images/' . $dirFile);
                            rmdir(base_path() . '/storage/tmp/imported_images');
                            rmdir(base_path() . '/storage/tmp');
                            $this->info("directory eliminata");
                        } catch (ErrorException $e) {
                            $this->info("directory non eliminata. Alcuni file sono ancora presenti nella cartella temporanea");
                        }
                    }
                }
            } else {
                $pathInfoFile = pathinfo($file);
                $contents = file_get_contents(base_path() . '/storage/tmp/imported_images/' . $pathInfoFile['basename']);
                if (in_array($pathInfoFile['extension'], $allowedMimeTypes)) {
                    
                    Log::info('Waiting 2 seconds...');
                    sleep(2);

                    $newEcmedia = EcMedia::create(['name' => $pathInfoFile['filename'], 'url' => '']);
                    Storage::disk('public')->put('ec_media/' . $newEcmedia->id, $contents);
                    $newEcmedia->url = 'ec_media/' . $newEcmedia->id;
                    $newEcmedia->save();
                    $this->info("Created EcMedia with id : $newEcmedia->id");
                    $createdEcMedia[] = $newEcmedia->id;
                    unlink(base_path() . '/storage/tmp/imported_images/' . $pathInfoFile['basename']);
                }
                try {
                    rmdir(base_path() . '/storage/tmp/imported_images');
                    rmdir(base_path() . '/storage/tmp');
                    $this->info("directory eliminata");
                } catch (ErrorException $e) {
                    $this->info("directory non eliminata. Alcuni file sono ancora presenti nella cartella temporanea");
                }

            }
        }
        return $createdEcMedia;
    }
}
