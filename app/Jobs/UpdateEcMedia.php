<?php

namespace App\Jobs;

use Exception;
use App\Models\EcMedia;
use App\Models\EcTrack;
use App\Models\TaxonomyWhere;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Intervention\Image\Exception\ImageException;

/**
 * Updates EcMedia: geometry, thumbnails and url
 */
class UpdateEcMedia implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $ecMedia;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(EcMedia $ecMedia)
    {
        $this->ecMedia = $ecMedia;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $this->enrichJob();
    }

    /**
     * Copied (and updated) from geomixer
     *
     * @throws Exception
     */
    public function enrichJob(): void
    {
        $thumbnailList = [];

        $imagePath = Storage::disk('public')->path($this->ecMedia->path);

        $exif = $this->getImageExif($imagePath);
        $ids = [];
        $ecMediaCoordinatesJson = [];
        if (isset($exif['coordinates'])) {;
            $geojson = [
                'type' => 'Point',
                'coordinates' => [$exif['coordinates'][0], $exif['coordinates'][1]]
            ];
            //updating ecmedia geometry based on exif coordinates
            $this->ecMedia->geometry = DB::raw("public.ST_Force2D(public.ST_GeomFromGeojson('" . json_encode($geojson) . "'))");
        }


        $imageCloudUrl = $this->uploadEcMediaImage($imagePath);
        if (is_null($imageCloudUrl)) {
            throw new Exception("Missing mandatory parameter: URL");
        }

        $sizes = config('geohub.ec_media.thumbnail_sizes');

        foreach ($sizes as $size) {
            try {
                if ($size['width'] == 0) {
                    $imageResize = $this->imgResizeSingleDimension($imagePath, $size['height'], 'height');
                } elseif ($size['height'] == 0) {
                    $imageResize = $this->imgResizeSingleDimension($imagePath, $size['width'], 'width');
                } else {
                    $imageResize = $this->imgResize($imagePath, $size['width'], $size['height']);
                }
                if (file_exists($imageResize)) {
                    $thumbnailUrl = $this->uploadEcMediaImageResize($imageResize, $size['width'], $size['height']);
                    if ($size['width'] == 0)
                        $key = 'x' . $size['height'];
                    elseif ($size['height'] == 0)
                        $key = $size['width'] . 'x';
                    else
                        $key = $size['width'] . 'x' . $size['height'];

                    $thumbnailList[$key] = $thumbnailUrl;
                }
            } catch (Exception $e) {
                Log::warning($e->getMessage());
            }
        }

        $this->ecMedia->url = $imageCloudUrl;
        $this->ecMedia->thumbnails = $thumbnailList;
        //persists changes on the database
        $this->ecMedia->saveQuietly();
    }

    /**
     * Return a mapped array with all the useful exif of the image
     * Copied (and updated) from geomixer
     *
     * @param string $imagePath the path of the image
     *
     * @return array the array with the coordinates
     *
     * @throws Exception
     */
    public function getImageExif(string $imagePath): array
    {

        Log::info("getImageExif");

        if (!file_exists($imagePath))
            throw new Exception("The image $imagePath does not exists");

        $data = Image::make($imagePath)->exif();

        if (isset($data['GPSLatitude']) && isset($data['GPSLongitude'])) {
            Log::info("getImageExif: Coordinates present");
            try {

                //Calculate Latitude with degrees, minutes and seconds

                $latDegrees = $data['GPSLatitude'][0];
                $latDegrees = explode('/', $latDegrees);
                $latDegrees = ($latDegrees[0] / $latDegrees[1]);

                $latMinutes = $data['GPSLatitude'][1];
                $latMinutes = explode('/', $latMinutes);
                $latMinutes = (($latMinutes[0] / $latMinutes[1]) / 60);

                $latSeconds = $data['GPSLatitude'][2];
                $latSeconds = explode('/', $latSeconds);
                $latSeconds = (($latSeconds[0] / $latSeconds[1]) / 3600);

                //Calculate Longitude with degrees, minutes and seconds

                $lonDegrees = $data['GPSLongitude'][0];
                $lonDegrees = explode('/', $lonDegrees);
                $lonDegrees = ($lonDegrees[0] / $lonDegrees[1]);

                $lonMinutes = $data['GPSLongitude'][1];
                $lonMinutes = explode('/', $lonMinutes);
                $lonMinutes = (($lonMinutes[0] / $lonMinutes[1]) / 60);

                $lonSeconds = $data['GPSLongitude'][2];
                $lonSeconds = explode('/', $lonSeconds);
                $lonSeconds = (($lonSeconds[0] / $lonSeconds[1]) / 3600);

                $imgLatitude = $latDegrees + $latMinutes + $latSeconds;
                $imgLongitude = $lonDegrees + $lonMinutes + $lonSeconds;

                $coordinates = [$imgLongitude, $imgLatitude];

                return array('coordinates' => $coordinates);
            } catch (Exception $e) {
                Log::info("getImageExif: invalid Coordinates present");
                return [];
            }
        } else {
            return [];
        }
    }


    public function getStorageDisk()
    {
        //TODO: remove this dynamic disk usage, use s3 with minio for local/dev development
        return config('geohub.use_local_storage') ? 'public' : 's3';
    }



    /**
     * Upload an existing image to the s3 bucket
     * Copied (and updated) from geomixer
     *
     * @param string $imagePath the path of the image to upload
     *
     * @return string the uploaded image url
     *
     * @throws Exception
     */
    public function uploadEcMediaImage(string $imagePath): string
    {
        if (!file_exists($imagePath))
            throw new Exception("The image $imagePath does not exists");

        $filename = pathinfo($imagePath)['filename'] . '.' . pathinfo($imagePath)['extension'];

        $cloudPath = 'EcMedia/' . $filename;



        $disk = $this->getStorageDisk();
        Storage::disk()->put('EcMedia/' . $filename, file_get_contents($imagePath));

        return Storage::disk($disk)->url($cloudPath);
    }


    /**
     * Upload an already resized image to the s3 bucket
     *
     * @param string $imagePath the resized image
     * @param int    $width     the image width
     * @param int    $height    the image height
     *
     * @return string the uploaded image url
     *
     * @throws Exception
     */
    public function uploadEcMediaImageResize(string $imagePath, int $width, int $height): string
    {
        Log::info("Uploading Image to " . STORAGE);
        if (!file_exists($imagePath))
            throw new Exception("The image $imagePath does not exists");

        $filename = basename($imagePath);
        if ($width == 0)
            $cloudPath = 'EcMedia/Resize/x' . $height . DIRECTORY_SEPARATOR . $filename;
        elseif ($height == 0)
            $cloudPath = 'EcMedia/Resize/' . $width . 'x' . DIRECTORY_SEPARATOR . $filename;
        else
            $cloudPath = 'EcMedia/Resize/' . $width . 'x' . $height . DIRECTORY_SEPARATOR . $filename;


        $disk = $this->getStorageDisk();

        Storage::disk($disk)->put($cloudPath, file_get_contents($imagePath));

        return Storage::disk($disk)->url($cloudPath);
    }


    /**
     * Resize the given image to the specified width and height
     * Copied (and updated) from geomixer
     *
     * @param string $imagePath the path of the image
     * @param int    $dim       the new width or height
     * @param string $type      the width or height
     *
     * @return string the new path image
     *
     * @throws ImageException
     */
    public function imgResizeSingleDimension(string $imagePath, int $dim, string $type): string
    {
        list($imgWidth, $imgHeight) = getimagesize($imagePath);
        if ($type == 'height') {
            if ($imgHeight < $dim)
                throw new ImageException("The image is too small to resize ");

            $img = $this->correctImageOrientation(Image::make($imagePath));
            $pathInfo = pathinfo($imagePath);
            $newPathImage = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $this->resizedFileName($imagePath, $width = '', $dim);
            $img->fit(null, $dim, function ($const) {
                $const->aspectRatio();
            })->save($newPathImage);

            return $newPathImage;
        } elseif ($type == 'width') {
            if ($imgWidth < $dim)
                throw new ImageException("The image is too small to resize ");

            $img = $this->correctImageOrientation(Image::make($imagePath));
            $pathInfo = pathinfo($imagePath);
            $newPathImage = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $this->resizedFileName($imagePath, $dim, $height = 0);
            $img->fit($dim, null, function ($const) {
                $const->aspectRatio();
            })->save($newPathImage);

            return $newPathImage;
        }
    }

    /**
     * Corregge l'orientamento dell'immagine basato sui dati Exif.
     * Copied (and updated) from geomixer
     *
     * @param \Intervention\Image\Image $img
     * @return \Intervention\Image\Image
     */
    public function correctImageOrientation($img)
    {
        $orientation = $img->exif('Orientation');
        switch ($orientation) {
            case 3:
                $img->rotate(180);
                break;
            case 6:
                $img->rotate(-90);
                break;
            case 8:
                $img->rotate(90);
                break;
        }
        return $img;
    }

    /**
     * Helper to get the filename of a resized image
     * Copied (and updated) from geomixer
     *
     * @param string $imagePath absolute path of file
     * @param int    $width     the image width
     * @param int    $height    the image height
     *
     * @return string
     */
    public function resizedFileName(string $imagePath, int $width, int $height): string
    {
        $pathInfo = pathinfo($imagePath);
        if ($width == 0)
            return $pathInfo['filename'] . '_x' . $height . '.' . $pathInfo['extension'];
        elseif ($height == 0)
            return $pathInfo['filename'] . '_' . $width . 'x.' . $pathInfo['extension'];
        else
            return $pathInfo['filename'] . '_' . $width . 'x' . $height . '.' . $pathInfo['extension'];
    }


    /**
     * Resize the given image to the specified width and height
     * Copied (and updated) from geomixer
     *
     * @param string $imagePath the path of the image
     * @param int    $width     the new width
     * @param int    $height    the new height
     *
     * @return string the new path image
     *
     * @throws ImageException
     */
    public function imgResize(string $imagePath, int $width, int $height): string
    {
        list($imgWidth, $imgHeight) = getimagesize($imagePath);
        if ($imgWidth < $width || $imgHeight < $height)
            throw new ImageException("The image is too small to resize - required size: $width, $height - actual size: $imgWidth, $imgHeight");

        $img = $this->correctImageOrientation(Image::make($imagePath));
        $pathInfo = pathinfo($imagePath);
        $newPathImage = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $this->resizedFileName($imagePath, $width, $height);
        $img->fit($width, $height, function ($const) {
            $const->aspectRatio();
        })->save($newPathImage);

        return $newPathImage;
    }
}
