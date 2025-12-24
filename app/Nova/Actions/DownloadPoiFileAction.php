<?php

namespace App\Nova\Actions;

use App\Models\EcPoi;
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
     * @return mixed
     */
    public function handleRequest(ActionRequest $request)
    {
        // Check if resources are selected in the request
        $selectedResources = $request->resources ?? '';

        // If "select all matching" is used, get all POIs matching the filters
        if ($request->forAllMatchingResources()) {
            $pois = $request->toQuery()
                ->with(['taxonomyPoiTypes', 'taxonomyThemes', 'featureImage', 'ecMedia'])
                ->get();
        }
        // If specific resources are selected, get them with necessary relationships
        elseif (! empty($selectedResources) && $selectedResources !== 'all') {
            $resourceIds = explode(',', $selectedResources);
            $pois = EcPoi::with(['taxonomyPoiTypes', 'taxonomyThemes', 'featureImage', 'ecMedia'])
                ->whereIn('id', $resourceIds)
                ->get();
        }
        // No resources selected
        else {
            $pois = collect();
        }

        $filename = 'poi-file-template_'.date('Y-m-d_His').'.xlsx';

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
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, $models)
    {
        $filename = 'poi-file-template_'.date('Y-m-d_His').'.xlsx';

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
class PoiDataSheet implements FromArray, WithStyles, WithTitle
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
                $lngResult = DB::select('SELECT ST_X(ST_AsText(?)) As wkt', [$poi->geometry]);
                $latResult = DB::select('SELECT ST_Y(ST_AsText(?)) As wkt', [$poi->geometry]);
                if (! empty($lngResult) && ! empty($latResult)) {
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

        // Get translations and clean HTML tags
        $nameIt = html_entity_decode(strip_tags($poi->getTranslation('name', 'it', '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $nameEn = html_entity_decode(strip_tags($poi->getTranslation('name', 'en', '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $descriptionIt = html_entity_decode(strip_tags($poi->getTranslation('description', 'it', '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $descriptionEn = html_entity_decode(strip_tags($poi->getTranslation('description', 'en', '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $excerptIt = html_entity_decode(strip_tags($poi->getTranslation('excerpt', 'it', '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $excerptEn = html_entity_decode(strip_tags($poi->getTranslation('excerpt', 'en', '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');

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
class PoiTypesTaxonomiesSheet implements FromArray, WithStyles, WithTitle
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
        $header = PoiFileAction::buildTaxonomiesSheetHeader($this->availableLanguages);
        $rows = PoiFileAction::buildTaxonomiesSheetRows(
            $this->poiTypes,
            $this->poiThemes,
            $this->availableLanguages
        );

        return array_merge([$header], $rows);
    }

    /**
     * Get the sheet title.
     */
    public function title(): string
    {
        return PoiFileAction::TAXONOMIES_SHEET_TITLE;
    }

    /**
     * Apply styles to the sheet.
     */
    public function styles(Worksheet $sheet)
    {
        $totalColumns = PoiFileAction::getTaxonomiesSheetColumnsCount($this->availableLanguages);
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
