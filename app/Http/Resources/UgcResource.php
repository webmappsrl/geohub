<?php

namespace App\Http\Resources;

use App\Models\App;
use App\Models\User;
use App\Models\UgcPoi;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\JsonResource;

class UgcResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $geom = $this->getGeometry();
        $email = $this->getUserEmail();
        $rawData = $this->processRawData();
        $properties = $this->buildProperties($email, $rawData);

        return [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom, true),
        ];
    }

    /**
     * Get the geometry data for the resource.
     *
     * @return string
     */
    protected function getGeometry()
    {
        return $this->select(DB::raw('ST_AsGeoJSON(geometry) as geom'))
            ->where('id', $this->id)
            ->first()
            ->geom;
    }

    /**
     * Get the user email.
     *
     * @return string
     */
    protected function getUserEmail()
    {
        return User::find($this->user_id)->email;
    }

    /**
     * Process and prepare raw data.
     *
     * @return array
     */
    protected function processRawData()
    {
        $properties = $this->properties;
        $properties = $this->removeUnwantedKeys($properties);
        $properties = $this->flattenFormProperties($properties);

        if (isset($this->raw_data)) {
            return array_merge($properties, json_decode($this->raw_data, true));
        }

        return $properties;
    }

    /**
     * Remove unwanted keys from properties.
     *
     * @param array $properties
     * @return array
     */
    protected function removeUnwantedKeys($properties)
    {
        $keysToRemove = ['media', 'device', 'coordinateProperties'];

        foreach ($keysToRemove as $key) {
            if (isset($properties[$key])) {
                unset($properties[$key]);
            }
        }

        return $properties;
    }

    /**
     * Flatten form properties if they exist.
     *
     * @param array $properties
     * @return array
     */
    protected function flattenFormProperties($properties)
    {
        if (isset($properties['form']) && is_array($properties['form'])) {
            foreach ($properties['form'] as $key => $value) {
                $properties[$key] = $value;
            }
            unset($properties['form']);
        }

        return $properties;
    }

    /**
     * Build the properties array for the resource.
     *
     * @param string $email
     * @param array $rawData
     * @return array
     */
    protected function buildProperties($email, $rawData)
    {
        $properties = [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'app_id' => $this->app_id,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'raw_data' => $rawData,
            'user_email' => $email,
            'sku' => App::find($this->app_id)->sku,
        ];

        $properties['taxonomy_wheres'] = $this->getTaxonomyWheresString();

        return $properties;
    }

    /**
     * Get taxonomy wheres as a comma-separated string.
     *
     * @return string
     */
    protected function getTaxonomyWheresString()
    {
        $taxonomyWheres = $this->taxonomy_wheres;
        $taxonomyWheresNames = [];

        if (count($taxonomyWheres) > 0) {
            foreach ($taxonomyWheres as $taxonomyWhere) {
                $taxonomyWheresNames[] = $taxonomyWhere->name;
            }
            return implode(',', $taxonomyWheresNames);
        }

        return '';
    }
}
