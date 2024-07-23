<?php

namespace App\Nova;

use App\Nova\Filters\AppFilter;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Text;

use App\Nova\Filters\SchemaFilter;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use App\Nova\Filters\UgcCreationDateFilter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;
use Titasgailius\SearchRelations\SearchesRelations;
use Suenerds\NovaSearchableBelongsToFilter\NovaSearchableBelongsToFilter;
use App\Nova\Actions\ExportFormDataXLSX;
use App\Models\App;
use Exception;

class UgcTrack extends Resource
{
    use SearchesRelations;

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static string $model = \App\Models\UgcTrack::class;
    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';
    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name'
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
            //            ID::make(__('ID'), 'id')->sortable(),
            DateTime::make(__('Created At'), 'created_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            Text::make(__('Name'), 'name')->sortable(),
            Text::make(__('App'),  function ($model) {
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
                    HTML;
                }
                return '';
            })->asHtml(),
            BelongsTo::make(__('Creator'), 'user', User::class),
            Text::make(__('App id'), 'app_id')
                ->canSee(function ($request) {
                    return $request->user()->can('Admin', $this);
                })->onlyOnForms(),
            BelongsToMany::make(__('Taxonomy wheres')),
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
                        $formSchema = json_decode($app->track_acquisition_form, true);
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
            })->onlyOnIndex()->asHtml(),
            Boolean::make(__('Has gallery'), function ($model) {
                $gallery = $model->ugc_media;

                return count($gallery) > 0;
            })->onlyOnIndex(),
            Text::make(__('Form data'), function ($model) {
                $formData = json_decode($model->raw_data, true);
                $html = '<table style="width:100%; border-collapse: collapse;" border="1">';

                if (isset($formData)) {
                    $app_id = $model->app_id;
                    if ($app_id === 'it.net7.parcoforestecasentinesi') {
                        $app_id = 'it.netseven.forestecasentinesi';
                    }
                    $app = App::where('app_id', $app_id)->first();

                    if (isset($app)) {
                        $formSchema = json_decode($app->track_acquisition_form, true);
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

                            return $html;
                        }
                    }
                }
            })->onlyOnDetail()->asHtml(),
            WmEmbedmapsField::make(__('Map'), function ($model) {
                return [
                    'feature' => $model->getGeojson(),
                    'related' => $model->getRelatedUgcGeojson()
                ];
            })->onlyOnDetail(),
            BelongsToMany::make(__('UGC Medias'), 'ugc_media'),
            Code::Make(__('metadata'), 'metadata')->language('json')->rules('nullable', 'json')->help(
                'metadata of track'
            )->onlyOnDetail(),
            Text::make(__('Raw data'), function ($model) {
                $rawData = json_decode($model->raw_data, true);
                $result = [];

                foreach ($rawData as $key => $value) {
                    $result[] = $key . ' = ' . json_encode($value);
                }

                return join('<br>', $result);
            })->onlyOnDetail()->asHtml(),
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
            (new UgcCreationDateFilter),
            (new AppFilter),
            (new SchemaFilter('ugc_track')),


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
            (new ExportFormDataXLSX('ugc_tracks'))->canRun(function ($request, $model) {
                return true;
            }),
        ];
    }
}
