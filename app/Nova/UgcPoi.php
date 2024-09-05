<?php

namespace App\Nova;

use Exception;
use App\Models\App;
use App\Nova\Actions\ExportFormDataXLSX;
use App\Nova\Actions\ConvertUgcToEcPoiAction;
use App\Nova\Actions\CopyUgc;
use App\Nova\Filters\AppFilter;
use App\Nova\Filters\DateRange;
use App\Nova\Filters\ShareUgcPoiFilter;
use App\Nova\Filters\UgcCreationDateFilter;
use App\Nova\Filters\SchemaFilter;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Suenerds\NovaSearchableBelongsToFilter\NovaSearchableBelongsToFilter;
use Titasgailius\SearchRelations\SearchesRelations;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;
use Laravel\Nova\Fields\Heading;

class UgcPoi extends Resource
{
    use SearchesRelations;

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static string $model = \App\Models\UgcPoi::class;
    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';
    public static $search = [
        'name',
        'raw_data->waypointtype'
    ];
    public static array $searchRelations = [
        'taxonomy_wheres' => ['name']
    ];

    public static function group()
    {
        return __('User Generated Content');
    }

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        if ($request->user()->can('Admin')) {
            return $query;
        }
        return $query->whereIn('app_id', $request->user()->apps->pluck('app_id')->toArray());
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function fields(Request $request): array
    {
        return [
            DateTime::make(__('Created At'), 'created_at')
                ->sortable()
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->help(__('Creation date of the UGC.')),
            Text::make(__('Name'), 'name')
                ->sortable()
                ->help(__('Name entered by the user.')),
            Text::make(__('App'),  function ($model) {
                $help = '<p>App from which the UGC was submitted</p>';
                $app_id = $model->app_id;
                if ($app_id === 'it.net7.parcoforestecasentinesi') {
                    $app_id = 'it.netseven.forestecasentinesi';
                }
                $app = App::where('app_id', $app_id)->first();
                if ($app) {
                    $url = url("/resources/apps/{$app->id}");
                    return <<<HTML
                    <a 
                        href="{$url}" 
                        target="_blank" 
                        class="no-underline dim text-primary font-bold">
                       {$app->name}
                    </a> <br>
                    $help
                    HTML;
                }
                return $help;
            })
                ->asHtml(),
            BelongsTo::make(__('Creator'), 'user', User::class)
                ->help(__('Creator of the UGC (User-Generated Content).')),
            BelongsToMany::make(__('Taxonomy wheres'))
                ->searchable(),
            Text::make(__('Form'), function ($model) {
                $formData = json_decode($model->raw_data, true);
                $html = '<table style="width:100%; border-collapse: collapse;" border="1">';

                if (isset($formData)) {
                    $app_id = $model->app_id;
                    if ($app_id === 'it.net7.parcoforestecasentinesi') {
                        $app_id = 'it.netseven.forestecasentinesi';
                    }
                    $app = App::where('app_id', $app_id)->first();

                    if (isset($app)) {
                        $formSchema = json_decode($app->poi_acquisition_form, true);
                        // Trova lo schema corretto basato sull'ID in $formData
                        try {
                            $currentSchema = collect($formSchema)->firstWhere('id', $formData['id']);
                        } catch (Exception $e) {
                            $currentSchema = null;
                        }

                        if ($currentSchema) {
                            // Aggiungi una riga all'inizio per il tipo di form
                            $typeLabel = reset($currentSchema['label']); // Assumi che 'label' esista e abbia almeno una voce
                            $html = '<strong>' . htmlspecialchars($typeLabel) . '</strong>';
                            return $html;
                        }
                    }
                }
            })
                ->onlyOnIndex()
                ->asHtml(),
            Boolean::make(__('Has gallery'), function ($model) {
                $gallery = $model->ugc_media;
                return count($gallery) > 0;
            })->onlyOnIndex(),
            Text::make(__('Form data'), function ($model) {
                $formData = json_decode($model->raw_data, true);
                $html = '<table style="width:100%; border-collapse: collapse;" border="1">';
                $help = '<p>Type of form submitted, and data entered within it</p>';

                if (isset($formData)) {
                    $app_id = $model->app_id;
                    if ($app_id === 'it.net7.parcoforestecasentinesi') {
                        $app_id = 'it.netseven.forestecasentinesi';
                    }
                    $app = App::where('app_id', $app_id)->first();

                    if (isset($app)) {
                        $formSchema = json_decode($app->poi_acquisition_form, true);
                        // Trova lo schema corretto basato sull'ID in $formData
                        try {
                            $currentSchema = collect($formSchema)->firstWhere('id', $formData['id']);
                        } catch (Exception $e) {
                            $currentSchema = null;
                        }

                        if ($currentSchema) {
                            // Aggiungi una riga all'inizio per il tipo di form
                            $typeLabel = reset($currentSchema['label']); // Assumi che 'label' esista e abbia almeno una voce
                            $html .= '<td><strong>tipo di form</strong></td><td>' . htmlspecialchars($typeLabel) . '</td>';

                            foreach ($currentSchema['fields'] as $field) {
                                $fieldLabel = reset($field['label']);
                                $fieldName = $field['name'];
                                if ($field['type'] === 'select' && isset($formData[$fieldName])) {
                                    $selectedValue = $formData[$fieldName];
                                    $fieldValue = null; // Default se non trovato
                                    // Trova la label corrispondente al valore selezionato
                                    foreach ($field['values'] as $option) {
                                        if ($option['value'] === $selectedValue) {
                                            $fieldValue = reset($option['label']);
                                            break;
                                        }
                                    }
                                } else {
                                    $fieldValue = isset($formData[$fieldName]) ? $formData[$fieldName] : null;
                                }

                                if (isset($fieldValue)) {
                                    $html .= '<tr>';
                                    $html .= '<td><strong>' . htmlspecialchars($fieldLabel) . '</strong></td>';
                                    $html .= '<td>' . htmlspecialchars($fieldValue) . '</td>';
                                    $html .= '</tr>';
                                }
                            }
                            $html .= '</table>';

                            return $html . $help;
                        }
                    }
                }
            })->onlyOnDetail()->asHtml(),
            Heading::make('<p>Geolocated point where the UGC was submitted</p>')
                ->asHtml()
                ->onlyOnDetail(),
            WmEmbedmapsField::make(__('Map'), function ($model) {
                return [
                    'feature' => $model->getGeojson(),
                    'related' => $model->getRelatedUgcGeojson()
                ];
            })->onlyOnDetail(),
            BelongsToMany::make(__('UGC Medias'), 'ugc_media'),
            Code::make(__('json Form data'), function ($model) {
                $jsonRawData = json_decode($model->raw_data, true);
                unset($jsonRawData['position']);
                unset($jsonRawData['displayPosition']);
                unset($jsonRawData['city']);
                unset($jsonRawData['date']);
                unset($jsonRawData['nominatim']);
                $rawData = json_encode($jsonRawData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                return  $rawData;
            })
                ->onlyOnDetail()
                ->language('json')
                ->rules('json'),
            Code::make(__('Device data'), function ($model) {
                $jsonRawData = json_decode($model->raw_data, true);
                $jsonData['position'] = $jsonRawData['position'];
                $jsonData['displayPosition'] = $jsonRawData['displayPosition'];
                $jsonData['city'] = $jsonRawData['city'];
                $jsonData['date'] = $jsonRawData['date'];
                $rawData = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                return  $rawData;
            })
                ->onlyOnDetail()
                ->language('json')
                ->rules('json'),
            Code::make(__('Nominatim'), function ($model) {
                $jsonData = json_decode($model->raw_data, true)['nominatim'];
                $rawData = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                return  $rawData;
            })
                ->onlyOnDetail()
                ->language('json')
                ->rules('json'),
            Code::make(__('Raw data'), function ($model) {
                $rawData = json_encode(json_decode($model->raw_data, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                return  $rawData;
            })
                ->onlyOnDetail()
                ->language('json')
                ->rules('json'),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param Request $request
     *
     * @return array
     */
    public function cards(Request $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function filters(Request $request): array
    {
        return [
            (new NovaSearchableBelongsToFilter('User'))
                ->fieldAttribute('user')
                ->filterBy('user_id'),
            (new UgcCreationDateFilter()),
            (new AppFilter()),
            (new ShareUgcPoiFilter()),
            (new SchemaFilter()),
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function lenses(Request $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function actions(Request $request): array
    {
        return [
            (new ConvertUgcToEcPoiAction())
                ->confirmText('The current user ID will be used for the new EcPois. Are you sure you want to convert to EcPoi?')
                ->canRun(function ($request, $model) {
                    return true;
                }),
            (new ExportFormDataXLSX('ugc_pois'))->canRun(function ($request, $model) {
                return true;
            }),
            (new CopyUgc())->canSee(function ($request) {
                return $request->user()->hasRole('Admin');
            })->canRun(function ($request, $zone) {
                return $request->user()->hasRole('Admin');
            }),
        ];
    }
}
