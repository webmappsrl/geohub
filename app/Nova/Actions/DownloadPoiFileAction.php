<?php

namespace App\Nova\Actions;

use App\Models\EcPoi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\ActionRequest;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DownloadPoiFileAction extends PoiFileAction
{
    /**
     * Handle the action request.
     *
     * @param  \Laravel\Nova\Http\Requests\ActionRequest  $request
     * @return mixed
     */
    public function handleRequest(ActionRequest $request)
    {
        // Check if resources are selected in the request
        $selectedResources = $request->resources ?? '';

        // If resources are selected, get them with necessary relationships
        if (!empty($selectedResources) && $selectedResources !== 'all') {
            $resourceIds = explode(',', $selectedResources);
            $pois = EcPoi::with(['taxonomyPoiTypes', 'taxonomyThemes', 'featureImage', 'ecMedia'])
                ->whereIn('id', $resourceIds)
                ->get();
        } else {
            $pois = collect();
        }

        $filename = 'poi-file-template_' . date('Y-m-d_His') . '.xlsx';

        $response = Excel::download(
            new PoiFileTemplateExport($this->getValidHeaders(), $this->getTaxonomiesData(), $pois),
            $filename
        );

        return Action::download(
            $this->getDownloadUrl($response->getFile()->getPathname(), $filename),
            $filename
        );
    }

    /**
     * Perform the action on the given models.
     * This method is kept for compatibility but handleRequest is used instead.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, $models)
    {
        $filename = 'poi-file-template_' . date('Y-m-d_His') . '.xlsx';

        // Check if POIs are selected
        $pois = $models->isNotEmpty() ? $models : collect();

        $response = Excel::download(
            new PoiFileTemplateExport($this->getValidHeaders(), $this->getTaxonomiesData(), $pois),
            $filename
        );

        return Action::download(
            $this->getDownloadUrl($response->getFile()->getPathname(), $filename),
            $filename
        );
    }

    /**
     * Get the download URL for the file.
     *
     * @param  string  $filePath
     * @param  string  $filename
     * @return string
     */
    protected function getDownloadUrl(string $filePath, string $filename): string
    {
        return URL::temporarySignedRoute('laravel-nova-excel.download', now()->addMinutes(1), [
            'path' => encrypt($filePath),
            'filename' => $filename,
        ]);
    }

    /**
     * Get the displayable name of the action.
     *
     * @return string
     */
    public function name()
    {
        return __('Download Poi File');
    }

    /**
     * Indicate that this action is available on the resource index.
     * Can be used standalone or with selected resources.
     */
    public function __construct()
    {
        $this->onlyOnIndex();
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }
}

/**
 * Export class for POI file template with first sheet (empty or with data) and taxonomies sheet.
 */
class PoiFileTemplateExport implements WithMultipleSheets
{
    use Exportable;

    protected $validHeaders;
    protected $taxonomiesData;
    protected $pois;

    public function __construct(array $validHeaders, array $taxonomiesData, $pois = null)
    {
        $this->validHeaders = $validHeaders;
        $this->taxonomiesData = $taxonomiesData;
        $this->pois = $pois ?? collect();
    }

    public function sheets(): array
    {
        return [
            new PoiDataSheet($this->validHeaders, $this->pois),
            new PoiTypesTaxonomiesSheet(
                $this->taxonomiesData['poiTypes'],
                $this->taxonomiesData['poiThemes'],
                $this->taxonomiesData['languages']
            ),
        ];
    }
}

/**
 * Sheet class for POI data (with headers and optionally POI data rows).
 */
class PoiDataSheet implements FromArray, WithTitle, WithStyles
{
    protected $headers;
    protected $pois;

    public function __construct(array $headers, $pois = null)
    {
        $this->headers = $headers;
        $this->pois = $pois ?? collect();
    }

    /**
     * Get the array data for the sheet.
     */
    public function array(): array
    {
        $data = [$this->headers];

        // If POIs are provided, map them to rows
        if ($this->pois->isNotEmpty()) {
            foreach ($this->pois as $poi) {
                $data[] = $this->mapPoiToRow($poi);
            }
        }

        return $data;
    }

    /**
     * Map a POI to a row matching the valid headers structure.
     */
    protected function mapPoiToRow(EcPoi $poi): array
    {
        // Load relationships
        $poi->load(['taxonomyPoiTypes', 'taxonomyThemes', 'featureImage', 'ecMedia']);

        // Get coordinates from geometry
        $lat = 0;
        $lng = 0;
        if ($poi->geometry) {
            try {
                $lngResult = DB::select("SELECT ST_X(ST_AsText(?)) As wkt", [$poi->geometry]);
                $latResult = DB::select("SELECT ST_Y(ST_AsText(?)) As wkt", [$poi->geometry]);
                if (!empty($lngResult) && !empty($latResult)) {
                    $lng = $lngResult[0]->wkt ?? 0;
                    $lat = $latResult[0]->wkt ?? 0;
                }
            } catch (\Exception $e) {
                // Keep default 0,0 if geometry parsing fails
            }
        }

        // Get POI type identifiers (comma-separated)
        $poiType = '';
        if ($poi->taxonomyPoiTypes->isNotEmpty()) {
            $poiType = $poi->taxonomyPoiTypes->pluck('identifier')->implode(',');
        }

        // Get theme identifiers (comma-separated)
        $theme = '';
        if ($poi->taxonomyThemes->isNotEmpty()) {
            $theme = $poi->taxonomyThemes->pluck('identifier')->implode(',');
        }

        // Get feature image URL
        $featureImage = '';
        if ($poi->featureImage) {
            if (strpos($poi->featureImage->url, 'ecmedia') !== false) {
                $featureImage = $poi->featureImage->url;
            } else {
                $featureImage = Storage::disk('public')->url($poi->featureImage->url);
            }
        }

        // Get gallery URLs (comma-separated)
        $gallery = '';
        if ($poi->ecMedia->isNotEmpty()) {
            $galleryUrls = $poi->ecMedia->map(function ($media) {
                if (strpos($media->url, 'ecmedia') !== false) {
                    return $media->url;
                }
                return Storage::disk('public')->url($media->url);
            })->toArray();
            $gallery = implode(',', $galleryUrls);
        }

        // Get translations
        $nameIt = $poi->getTranslation('name', 'it', '');
        $nameEn = $poi->getTranslation('name', 'en', '');
        $descriptionIt = $poi->getTranslation('description', 'it', '');
        $descriptionEn = $poi->getTranslation('description', 'en', '');
        $excerptIt = $poi->getTranslation('excerpt', 'it', '');
        $excerptEn = $poi->getTranslation('excerpt', 'en', '');

        // Get related_url (can be array or string)
        $relatedUrl = '';
        if ($poi->related_url) {
            if (is_array($poi->related_url)) {
                $relatedUrl = implode(',', array_values($poi->related_url));
            } else {
                $relatedUrl = $poi->related_url;
            }
        }

        // Build row in the exact order of valid headers (excluding 'errors')
        $row = [];
        foreach ($this->headers as $header) {
            switch ($header) {
                case 'id':
                    $row[] = $poi->id ?? '';
                    break;
                case 'name_it':
                    $row[] = $nameIt;
                    break;
                case 'name_en':
                    $row[] = $nameEn;
                    break;
                case 'description_it':
                    $row[] = $descriptionIt;
                    break;
                case 'description_en':
                    $row[] = $descriptionEn;
                    break;
                case 'excerpt_it':
                    $row[] = $excerptIt;
                    break;
                case 'excerpt_en':
                    $row[] = $excerptEn;
                    break;
                case 'poi_type':
                    $row[] = $poiType;
                    break;
                case 'lat':
                    $row[] = $lat;
                    break;
                case 'lng':
                    $row[] = $lng;
                    break;
                case 'addr_complete':
                    $row[] = $poi->addr_complete ?? '';
                    break;
                case 'capacity':
                    $row[] = $poi->capacity ?? '';
                    break;
                case 'contact_phone':
                    $row[] = $poi->contact_phone ?? '';
                    break;
                case 'contact_email':
                    $row[] = $poi->contact_email ?? '';
                    break;
                case 'related_url':
                    $row[] = $relatedUrl;
                    break;
                case 'feature_image':
                    $row[] = $featureImage;
                    break;
                case 'gallery':
                    $row[] = $gallery;
                    break;
                case 'theme':
                    $row[] = $theme;
                    break;
                default:
                    $row[] = '';
                    break;
            }
        }

        return $row;
    }

    /**
     * Get the sheet title.
     */
    public function title(): string
    {
        return 'POI Data';
    }

    /**
     * Apply styles to the sheet.
     */
    public function styles(Worksheet $sheet)
    {
        $totalColumns = count($this->headers);
        $lastColumn = Coordinate::stringFromColumnIndex($totalColumns);

        // Make header row bold
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);

        // Auto-size all columns
        for ($col = 1; $col <= $totalColumns; $col++) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }
    }
}

/**
 * Sheet class for POI Types Taxonomies.
 */
class PoiTypesTaxonomiesSheet implements FromArray, WithTitle, WithStyles
{
    protected $poiTypes;
    protected $poiThemes;
    protected $availableLanguages;

    public function __construct(array $poiTypes, array $poiThemes, array $availableLanguages = [])
    {
        $this->poiTypes = $poiTypes;
        $this->poiThemes = $poiThemes;
        $this->availableLanguages = $availableLanguages;
    }

    /**
     * Get the array data for the sheet.
     */
    public function array(): array
    {
        // Build header row
        $header = ['POI Type ID', 'Available POI Type Identifiers'];

        // Add columns for each available language
        foreach ($this->availableLanguages as $lang) {
            $header[] = 'Available POI Type Names ' . strtoupper($lang);
        }

        // Add theme identifiers column
        $header[] = 'Available POI Theme Identifiers';

        $data = [$header];

        $maxRows = max(count($this->poiTypes), count($this->poiThemes));

        for ($i = 0; $i < $maxRows; $i++) {
            $poiTypeId = '';
            $poiTypeIdentifier = '';
            $poiTypeNames = [];

            if (isset($this->poiTypes[$i])) {
                if (is_array($this->poiTypes[$i])) {
                    $poiTypeId = $this->poiTypes[$i]['id'] ?? '';
                    $poiTypeIdentifier = $this->poiTypes[$i]['identifier'] ?? '';
                    $poiTypeNames = $this->poiTypes[$i]['names'] ?? [];
                } else {
                    // Backward compatibility: if it's just a string
                    $poiTypeIdentifier = $this->poiTypes[$i];
                }
            }

            // Build row: ID, Identifier, then names for each language, then themes
            $row = [
                $poiTypeId,
                $poiTypeIdentifier,
            ];

            // Add name for each available language (empty if not available for this POI type)
            foreach ($this->availableLanguages as $lang) {
                $row[] = $poiTypeNames[$lang] ?? '';
            }

            // Add theme identifier
            $row[] = $this->poiThemes[$i] ?? '';

            $data[] = $row;
        }

        return $data;
    }

    /**
     * Get the sheet title.
     */
    public function title(): string
    {
        return 'POI Types Taxonomies';
    }

    /**
     * Apply styles to the sheet.
     */
    public function styles(Worksheet $sheet)
    {
        // Calculate total number of columns: 2 (ID, Identifier) + languages + 1 (Themes)
        $totalColumns = 2 + count($this->availableLanguages) + 1;
        $lastColumn = Coordinate::stringFromColumnIndex($totalColumns);

        // Make header row bold
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);

        // Auto-size all columns
        for ($col = 1; $col <= $totalColumns; $col++) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }
    }
}
