<?php

namespace App\Nova\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DownloadPoiFileAction extends Action
{
    private const ERROR_COLUMN_NAME = 'errors';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, $models)
    {
        $filename = 'poi-file-template_' . date('Y-m-d_His') . '.xlsx';

        $response = Excel::download(
            new PoiFileTemplateExport($this->getValidHeaders(), $this->getTaxonomiesData()),
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
     * Indicate that this action is only available on the resource index.
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

    /**
     * Get valid headers from configuration.
     *
     * @return array Array of valid headers
     */
    private function getValidHeaders(): array
    {
        return array_filter(
            config('services.importers.ecPois.validHeaders'),
            fn($header) => $header !== self::ERROR_COLUMN_NAME
        );
    }

    /**
     * Get POI types taxonomies data for the sheet.
     */
    private function getTaxonomiesData(): array
    {
        // Get POI types with id, identifier and name, ordered by id ascending
        $poiTypesData = DB::table('taxonomy_poi_types')
            ->select('id', 'identifier', 'name')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($poiType) {
                $names = [];
                if ($poiType->name) {
                    $nameArray = is_string($poiType->name) ? json_decode($poiType->name, true) : $poiType->name;
                    if (is_array($nameArray)) {
                        // Get all available translations, filtering out empty/null values
                        foreach ($nameArray as $lang => $value) {
                            if (!empty($value) && $value !== null) {
                                $names[$lang] = $value;
                            }
                        }
                    }
                }
                return [
                    'id' => $poiType->id,
                    'identifier' => $poiType->identifier,
                    'names' => $names,
                ];
            })
            ->toArray();

        // Collect all available languages from all POI types
        $availableLanguages = [];
        foreach ($poiTypesData as $poiType) {
            if (isset($poiType['names']) && is_array($poiType['names'])) {
                $availableLanguages = array_merge($availableLanguages, array_keys($poiType['names']));
            }
        }
        $availableLanguages = array_unique($availableLanguages);
        // Sort languages in a consistent order based on project supported languages
        // Project supported languages: it, en, fr, de, es, nl, sq (from config/tab-translatable.php)
        $languageOrder = ['it', 'en', 'fr', 'de', 'es', 'nl', 'sq'];
        $sortedLanguages = [];
        // First, add languages in the predefined order (if they exist in available languages)
        foreach ($languageOrder as $lang) {
            if (in_array($lang, $availableLanguages)) {
                $sortedLanguages[] = $lang;
            }
        }
        // Add any remaining languages not in the predefined order (in alphabetical order)
        $remainingLanguages = array_diff($availableLanguages, $sortedLanguages);
        sort($remainingLanguages);
        $sortedLanguages = array_merge($sortedLanguages, $remainingLanguages);

        // Get POI themes identifiers
        $poiThemes = [];
        if (auth()->check() && auth()->user()) {
            foreach (auth()->user()->apps as $app) {
                $themes = $app->taxonomyThemes()->pluck('identifier')->toArray();
                $poiThemes = array_merge($poiThemes, $themes);
            }
        }
        $poiThemes = array_unique($poiThemes);

        return [
            'poiTypes' => $poiTypesData,
            'poiThemes' => $poiThemes,
            'languages' => $sortedLanguages,
        ];
    }
}

/**
 * Export class for POI file template with empty first sheet and taxonomies sheet.
 */
class PoiFileTemplateExport implements WithMultipleSheets
{
    use Exportable;

    protected $validHeaders;
    protected $taxonomiesData;

    public function __construct(array $validHeaders, array $taxonomiesData)
    {
        $this->validHeaders = $validHeaders;
        $this->taxonomiesData = $taxonomiesData;
    }

    public function sheets(): array
    {
        return [
            new EmptyPoiDataSheet($this->validHeaders),
            new PoiTypesTaxonomiesSheet(
                $this->taxonomiesData['poiTypes'],
                $this->taxonomiesData['poiThemes'],
                $this->taxonomiesData['languages']
            ),
        ];
    }
}

/**
 * Sheet class for empty POI data (with headers only).
 */
class EmptyPoiDataSheet implements FromArray, WithTitle, WithStyles
{
    protected $headers;

    public function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Get the array data for the sheet.
     */
    public function array(): array
    {
        // Return only the header row
        return [$this->headers];
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
