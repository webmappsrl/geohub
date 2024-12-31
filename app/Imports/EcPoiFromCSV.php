<?php

namespace App\Imports;

use Exception;
use App\Models\EcPoi;
use App\Models\EcMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EcPoiFromCSV implements ToModel, WithHeadingRow, WithMultipleSheets
{
    public $errors = [];
    public $poiIds = [];
    private $currentRow = 2;
    public $poiTypes = [];
    public $poiThemes = [];
    private $user;


    public function __construct()
    {
        $this->poiTypes = DB::table('taxonomy_poi_types')->pluck('identifier')->toArray();
        $this->user = auth()->user();

        $poiThemes = [];

        foreach (auth()->user()->apps as $app) {
            $themes = $app->taxonomyThemes()->pluck('identifier')->toArray();
            $poiThemes = array_merge($poiThemes, $themes);
        }

        $this->poiThemes = $poiThemes;
    }

    /**
     * Necessary when multiple sheets are used (like in this case we have a support sheet), we only import from the first sheet
     * 
     * @return array
     */
    public function sheets(): array
    {
        return [
            0 => $this
        ];
    }


    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Skip empty rows
        if (empty(array_filter($row))) {
            $this->currentRow++;
            return null;
        }

        $userPois = $this->user->ecPois()->pluck('id')->toArray();
        $allPois = DB::table('ec_pois')->pluck('id')->toArray();
        $ecPoiData = $this->processRow($row);

        //Check if the poi belongs to the user
        try {
            if (!isset($ecPoiData['id'])) {
                $id = $this->buildEcPoi($ecPoiData);
                //if the poi is new we need to add the id to the excel file.
                $this->poiIds[] = ['row' => $this->currentRow, 'id' => $id];
            } elseif (!in_array($ecPoiData['id'], $userPois) && in_array($ecPoiData['id'], $allPois)) {
                throw new \Exception(__('The poi with ID ') . $ecPoiData['id'] . __(' is already in the database but it is not in your list. Please check the file and try again.'));
            } else {
                $this->updateEcPoi($ecPoiData, $user);
            }
        } catch (\Exception $e) {
            $this->errors[] = ['row' => $this->currentRow, 'message' => $e->getMessage()];
        }
        $this->currentRow++;
    }

    /**
     * Process the row data.
     *
     * @param array $row
     * @return array
     * @throws \Exception
     */
    private function processRow(array $row): array
    {
        $ecPoiData = [];
        $validHeaders = config('services.importers.ecPois.validHeaders');
        $fileHeaders = array_keys($row);
        $invalidHeaders = array_diff($fileHeaders, $validHeaders);

        $invalidHeaders = array_filter($invalidHeaders, function ($value) {
            return !is_numeric($value);
        });

        if (!empty($invalidHeaders)) {
            $errorMessage = __("Invalid headers found:") . implode(', ', $invalidHeaders) . __(". Please check the file and try again.");
            Log::error($errorMessage);
            throw new \Exception($errorMessage);
        }

        foreach ($row as $key => $value) {
            if (in_array($key, $validHeaders)) {
                $this->validatePoiData($key, $value);
                $ecPoiData[$key] = $value;
            }
        }

        $this->addGeometry($ecPoiData);
        $this->addUserId($ecPoiData, auth()->user()->id);

        return $ecPoiData;
    }

    /**
     * Validate Poi data.
     *
     * @param string $key
     * @param mixed $value
     * @throws \Exception
     */
    private function validatePoiData(string $key, $value): void
    {
        if ($key == 'name_it' && empty(trim($value))) {
            $this->errors[] = ['row' => $this->currentRow, 'message' => __('Poi name is mandatory. Please check the file and try again.')];
        }

        if ($key == 'poi_type') {
            if (empty(trim($value))) {
                $this->errors[] = ['row' => $this->currentRow, 'message' => __('At least one Poi type is mandatory. Please check the file and try again.')];
            }
            //if the poi type is not inside the poiTypes array, throw an error
            if (!in_array($value, $this->poiTypes)) {
                $this->errors[] = ['row' => $this->currentRow, 'message' => __('Invalid Poi type found: ') . $value . __('. Please check the support sheet for valid Poi types and try again.')];
            }
        }

        if ($key == 'theme') {
            if (!in_array($value, $this->poiThemes)) {
                $this->errors[] = ['row' => $this->currentRow, 'message' => __('Invalid theme found: ') . $value . __('. Please check the support sheet for valid themes and try again.')];
            }
        }

        if (($key == 'lat' || $key == 'lng') && empty(trim($value))) {
            $this->errors[] = ['row' => $this->currentRow, 'message' => __('Invalid coordinates found. Please check the file and try again.')];
        }

        if ($key == 'related_url' && !empty(trim($value))) {
            $this->validateUrl($value);
        }
    }

    /**
     * Validate URL.
     *
     * @param mixed $value
     * @throws \Exception
     */
    private function validateUrl($value): void
    {
        if (strpos($value, 'http://') === false && strpos($value, 'https://') === false) {
            throw new \Exception(__('Invalid URL found. Please check the file and try again.'));
        }
    }

    /**
     * Add geometry to Poi data.
     *
     * @param array $ecPoiData
     */
    private function addGeometry(array &$ecPoiData): void
    {
        $geom = '{"type":"Point","coordinates":[' . $ecPoiData['lng'] . ',' . $ecPoiData['lat'] . ']}';
        $geom = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('$geom')) As wkt")[0]->wkt;
        $ecPoiData['geometry'] = $geom;

        unset($ecPoiData['lat']);
        unset($ecPoiData['lng']);
    }

    /**
     * Add FeatureImage to Poi data.
     *
     * @param array $ecPoiData
     */
    private function addFeatureImage(array &$ecPoiData, $ecPoi)
    {
        $ecMedia = null;
        $storage = Storage::disk('public');
        try {
            $url = $ecPoiData['name_it'] . '.png';
            $contents = file_get_contents($ecPoiData['feature_image']);
            $storage->put($url, $contents); // salvo l'image sullo storage come concatenazione nome estensione

            $ecMedia = new EcMedia(['name' => $ecPoiData['name_it'], 'url' => $url, 'geometry' => $ecPoiData['geometry']]);
            $ecMedia->save();
        } catch (Exception $e) {
            Log::error(__("featureImage: create ec media -> ") . $e->getMessage());
        }
        $ecPoi->featureImage()->associate($ecMedia);
        unset($ecPoiData['feature_image']);
    }

    /**
     * Add user_id to Poi data.
     *
     * @param array $ecPoiData
     * @param int $user_id
     */
    private function addUserId(array &$ecPoiData, int $user_id): void
    {
        $ecPoiData['user_id'] = $user_id;
    }

    /**
     * Create EcPoi.
     *
     * @param array $ecPoiData
     * @return int The ID of the created EcPoi
     */
    private function buildEcPoi(array $ecPoiData): int
    {
        $ecPoi = EcPoi::Create(['name' => $ecPoiData['name_it']]);
        $this->setTranslations($ecPoi, $ecPoiData);
        $this->syncPoiTypesAndThemes($ecPoi, $ecPoiData);
        if (isset($ecPoiData['feature_image']) && !empty($ecPoiData['feature_image'])) {
            $this->addFeatureImage($ecPoiData, $ecPoi);
        }
        $ecPoi->update($ecPoiData);
        $ecPoi->save();
        return $ecPoi->id;
    }

    /** Update EcPoi
     *
     * @param array $ecPoiData
     */
    private function updateEcPoi(array $ecPoiData, $user): void
    {
        $ecPoi = EcPoi::find($ecPoiData['id']);
        if (isset($ecPoiData['feature_image']) && !empty($ecPoiData['feature_image'])) {
            $this->addFeatureImage($ecPoiData, $ecPoi);
        }
        $ecPoi->update($ecPoiData);
        $this->setTranslations($ecPoi, $ecPoiData);
        $this->syncPoiTypesAndThemes($ecPoi, $ecPoiData, $user);
        $ecPoi->save();
    }

    /**
     * Set translations for Poi.
     *
     * @param EcPoi $ecPoi
     * @param array $ecPoiData
     */
    private function setTranslations(EcPoi $ecPoi, array $ecPoiData): void
    {
        $italianName = $ecPoiData['name_it'];
        $englishName = $ecPoiData['name_en'] ?? '';
        $italianDescription = $ecPoiData['description_it'] ?? '';
        $englishDescription = $ecPoiData['description_en'] ?? '';
        $italianExcerpt = $ecPoiData['excerpt_it'] ?? '';
        $englishExcerpt = $ecPoiData['excerpt_en'] ?? '';

        $ecPoi->setTranslation('name', 'it', $italianName);
        $ecPoi->setTranslation('name', 'en', $englishName);
        $ecPoi->setTranslation('description', 'it', $italianDescription);
        $ecPoi->setTranslation('description', 'en', $englishDescription);
        $ecPoi->setTranslation('excerpt', 'it', $italianExcerpt);
        $ecPoi->setTranslation('excerpt', 'en', $englishExcerpt);
        $ecPoi->save();

        //unset the names and descriptions
        unset($ecPoiData['name_it']);
        unset($ecPoiData['name_en']);
        unset($ecPoiData['description_it']);
        unset($ecPoiData['description_en']);
        unset($ecPoiData['excerpt_it']);
        unset($ecPoiData['excerpt_en']);
    }

    /**
     * Sync Poi types and themes.
     *
     * @param EcPoi $ecPoi
     * @param array $ecPoiData
     */
    private function syncPoiTypesAndThemes(EcPoi $ecPoi, array $ecPoiData): void
    {
        $poiTypes = $this->getTaxonomyIds($ecPoiData['poi_type'], 'taxonomy_poi_types');
        $poiThemes = $this->getTaxonomyIds($ecPoiData['theme'], 'taxonomy_themes');

        if (count($poiTypes) > 0) {
            $ecPoi->taxonomyPoiTypes()->sync($poiTypes);
        }

        if (count($poiThemes) > 0) {
            $ecPoi->taxonomyThemes()->sync($poiThemes);
        } else {
            throw new \Exception(__('Invalid Poi theme found: ') . $ecPoiData['theme'] . __('. Please check the file and try again.'));
        }

        //unset the poi_type and theme
        unset($ecPoiData['poi_type']);
        unset($ecPoiData['theme']);
    }

    /**
     * Get Taxonomy IDs.
     *
     * @param mixed $taxonomy
     * @param string $table
     * @return array
     */
    private function getTaxonomyIds($taxonomy, string $table): array
    {
        $name = $table == 'taxonomy_poi_types' ? 'poi_type' : 'theme';
        if (strpos($taxonomy, ',') !== false) {
            $taxonomies = explode($taxonomy, ',');
        } else {
            $taxonomies = [$taxonomy];
        }
        try {
            $modelsId = DB::table($table)
                ->select('id')
                ->where(function ($query) use ($taxonomies) {
                    foreach ($taxonomies as $taxonomy) {
                        $query->where('identifier', $taxonomy);
                    }
                })
                ->pluck('id')
                ->toArray() ?? [];
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error(__("A database error occurred: ") . $e->getMessage());
            throw new \Exception($name . __(' not found in the database. Please check the file and try again.'));
        }

        return $modelsId;
    }
}
