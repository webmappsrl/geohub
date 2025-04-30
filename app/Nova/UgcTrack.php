<?php

namespace App\Nova;

use App\Models\App;
use App\Nova\Actions\CopyUgc;
use App\Nova\Actions\ExportFormDataXLSX;
use App\Nova\Filters\AppFilter;
use App\Nova\Filters\SchemaFilter;
use App\Nova\Filters\UgcCreationDateFilter;
use App\Nova\Filters\UgcUserRelationFilter;
use Exception;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Suenerds\NovaSearchableBelongsToFilter\NovaSearchableBelongsToFilter;
use Titasgailius\SearchRelations\SearchesRelations;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;

class UgcTrack extends Resource
{
    use SearchesRelations;

    /**
     * The model the resource corresponds to.
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
        'name',
    ];

    public static array $searchRelations = [
        'taxonomy_wheres' => ['name'],
    ];

    public static function group()
    {
        return __('User Generated Content');
    }

    /**
     * Build an "index" query for the given resource.
     *
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
            Text::make(__('App'), function ($model) {
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
                $formData = $model->properties['form'] ?? [];
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
            WmEmbedmapsField::make(__('Geometry'), function ($model) {
                return [
                    'feature' => $model->getGeojson(),
                    'related' => $model->getRelatedUgcGeojson(),
                ];
            })->onlyOnDetail(),
            Heading::make('<p>properties</p>')
                ->asHtml()
                ->onlyOnDetail(),
            Text::make(__('App version'), function ($model) {
                $properties = $model->properties;
                $device = $properties['device'] ?? null;
                $appVersion = $device['appVersion'] ?? null;

                return $appVersion;
            })
                ->onlyOnDetail()
                ->asHtml(),
            Text::make(__('Distance filter'), function ($model) {
                $properties = $model->properties;
                $distanceFilter = $properties['distanceFilter'] ?? null;

                return $distanceFilter;
            })
                ->onlyOnDetail()
                ->asHtml(),
            Text::make(__('Form'), function ($model) {

                $formData = $model->properties['form'] ?? [];

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
            BelongsToMany::make(__('UGC Medias'), 'ugc_media'),
            Code::make(__('device'), 'properties')
                ->language('json')
                ->rules('nullable', 'json')
                ->help('metadata of track')
                ->onlyOnDetail()
                ->resolveUsing(function ($properties) {
                    return json_encode($properties['device'] ?? null, JSON_PRETTY_PRINT);
                }),
            Code::make(__('location'), 'properties')
                ->language('json')
                ->rules('nullable', 'json')
                ->help('metadata of track')
                ->onlyOnDetail()
                ->resolveUsing(function ($properties) {
                    return json_encode($properties['locations'] ?? null, JSON_PRETTY_PRINT);
                }),
            Code::Make(__('metadata'), 'metadata')
                ->language('json')
                ->rules('nullable', 'json')
                ->help('metadata of track')
                ->onlyOnDetail(),
            Text::make(__('Raw data'), function ($model) {
                $rawData = json_decode($model->raw_data, true);

                if (! is_array($rawData)) {
                    return 'Invalid raw data';
                }

                $result = [];

                foreach ($rawData as $key => $value) {
                    $result[] = $key . ' = ' . json_encode($value);
                }

                return implode('<br>', $result);
            })
                ->onlyOnDetail()
                ->asHtml(),
        ];
    }

    /**
     * Get the cards available for the request.
     */
    public function cards(Request $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     */
    public function filters(Request $request): array
    {
        return [
            (new AppFilter)
                ->setRelation('ugc_tracks'),
            (new UgcUserRelationFilter('User'))
                ->setRelation('ugc_tracks')
                ->fieldAttribute('user')
                ->filterBy('user_id'),
            (new UgcCreationDateFilter),
            (new SchemaFilter('ugc_track')),

        ];
    }

    /**
     * Get the lenses available for the resource.
     */
    public function lenses(Request $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     */
    public function actions(Request $request): array
    {
        return [
            (new ExportFormDataXLSX('ugc_tracks'))->canRun(function ($request, $model) {
                return true;
            }),
            (new CopyUgc)->canSee(function ($request) {
                return $request->user()->hasRole('Admin');
            })->canRun(function ($request, $zone) {
                return $request->user()->hasRole('Admin');
            }),
        ];
    }
}
