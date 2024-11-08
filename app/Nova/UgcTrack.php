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
use App\Nova\Actions\CopyUgc;
use Exception;
use Laravel\Nova\Fields\Heading;

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
        $user = $request->user();
        $apps = $user->apps->pluck('id')->toArray();
        return $query->whereIn('app_id', $apps);
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
                ->help(__('Creation date of the UGC (User-Generated Content).')),
            Text::make(__('Name'), 'name')
                ->sortable()
                ->help(__('Name of the UGC (User-Generated Content).')),
            Text::make(__('App'),  function ($model) {
                $help = __('App from which the UGC was submitted.');
                $sku = $model->sku;
                $app = App::where('sku', $sku)->first();
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
                return $help;
            })->asHtml(),
            BelongsTo::make(__('Creator'), 'user', User::class)
                ->help(__('Creator of the UGC (User-Generated Content).')),
            Text::make(__('App id'), 'sku')
                ->canSee(function ($request) {
                    return $request->user()->can('Admin', $this);
                })
                ->onlyOnForms()
                ->help(__('Reference ID of the app SKU. If changed, the UGC (User-Generated Content) will no longer be visible for the current app.')),
            BelongsToMany::make(__('Taxonomy wheres')),
            Text::make(__('Form'), function ($model) {
                $formData = json_decode($model->raw_data, true);
                $html = '<table style="width:100%; border-collapse: collapse;" border="1">';

                if (isset($formData)) {
                    $sku = $model->sku;
                    $pnfcSku = ['it.net7.parcoforestecasentinesi', 'it.netseven.forestecasentinesi'];
                    $app = App::where('sku', $sku)->first();
                    if (in_array($sku, $pnfcSku)) {
                        $app = App::whereIn('sku', $pnfcSku)->first();
                    }

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
                    $sku = $model->sku;
                    if ($sku === 'it.net7.parcoforestecasentinesi') {
                        $sku = 'it.netseven.forestecasentinesi';
                    }
                    $app = App::where('sku', $sku)->first();

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

                            return $html . $help;
                        }
                    }
                }
            })
                ->onlyOnDetail()
                ->asHtml(),
            Heading::make('<p>Geolocated track created by the user</p>')
                ->asHtml()
                ->onlyOnDetail(),
            WmEmbedmapsField::make(__('Map'), function ($model) {
                return [
                    'feature' => $model->getGeojson(),
                    'related' => $model->getRelatedUgcGeojson()
                ];
            })->onlyOnDetail(),
            BelongsToMany::make(__('UGC Medias'), 'ugc_media'),
            Code::Make(__('metadata'), 'metadata')
                ->language('json')
                ->rules('nullable', 'json')
                ->help('metadata of track')
                ->onlyOnDetail(),
            Text::make(__('Raw data'), function ($model) {
                $rawData = json_decode($model->raw_data, true);
                $result = [];

                foreach ($rawData as $key => $value) {
                    $result[] = $key . ' = ' . json_encode($value);
                }

                return join('<br>', $result);
            })
                ->onlyOnDetail()
                ->asHtml(),
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
            (new CopyUgc())->canSee(function ($request) {
                return $request->user()->hasRole('Admin');
            })->canRun(function ($request, $zone) {
                return $request->user()->hasRole('Admin');
            }),
        ];
    }
}
