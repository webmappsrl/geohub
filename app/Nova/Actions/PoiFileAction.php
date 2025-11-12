<?php

namespace App\Nova\Actions;

use Illuminate\Support\Facades\DB;
use Laravel\Nova\Actions\Action;

/**
 * Base class for POI file actions.
 * Contains shared functionality for DownloadPoiFileAction and UploadPoiFile.
 */
abstract class PoiFileAction extends Action
{
    protected const ERROR_COLUMN_NAME = 'errors';
    public const TAXONOMIES_SHEET_TITLE = 'POI Types Taxonomies';
    public const ERROR_HIGHLIGHT_COLOR = 'FFFF00';

    /**
     * Get valid headers from configuration.
     *
     * @return array Array of valid headers (excluding 'errors')
     */
    protected function getValidHeaders(): array
    {
        return array_filter(
            config('services.importers.ecPois.validHeaders'),
            fn ($header) => $header !== self::ERROR_COLUMN_NAME
        );
    }

    /**
     * Get POI types taxonomies data for the sheet.
     *
     * @return array Array containing poiTypes, poiThemes, and languages
     */
    protected function getTaxonomiesData(): array
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
                            if (! empty($value) && $value !== null) {
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

    /**
     * Build header row for the taxonomies sheet.
     *
     * @param  array  $languages  Array of language codes
     * @return array Header row array
     */
    public static function buildTaxonomiesSheetHeader(array $languages): array
    {
        $header = ['POI Type ID', 'Available POI Type Identifiers'];
        foreach ($languages as $lang) {
            $header[] = 'Available POI Type Names ' . strtoupper($lang);
        }
        $header[] = 'Available POI Theme Identifiers';

        return $header;
    }

    /**
     * Build data rows for the taxonomies sheet.
     *
     * @param  array  $poiTypes  Array of POI types data
     * @param  array  $poiThemes  Array of POI themes identifiers
     * @param  array  $languages  Array of language codes
     * @return array Array of data rows
     */
    public static function buildTaxonomiesSheetRows(array $poiTypes, array $poiThemes, array $languages): array
    {
        $rows = [];
        $maxRows = max(count($poiTypes), count($poiThemes));

        for ($i = 0; $i < $maxRows; $i++) {
            $poiTypeId = '';
            $poiTypeIdentifier = '';
            $poiTypeNames = [];

            if (isset($poiTypes[$i])) {
                if (is_array($poiTypes[$i])) {
                    $poiTypeId = $poiTypes[$i]['id'] ?? '';
                    $poiTypeIdentifier = $poiTypes[$i]['identifier'] ?? '';
                    $poiTypeNames = $poiTypes[$i]['names'] ?? [];
                } else {
                    // Backward compatibility: if it's just a string
                    $poiTypeIdentifier = $poiTypes[$i];
                }
            }

            $row = [$poiTypeId, $poiTypeIdentifier];

            foreach ($languages as $lang) {
                $row[] = $poiTypeNames[$lang] ?? '';
            }

            $row[] = $poiThemes[$i] ?? '';

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Calculate the total number of columns for the taxonomies sheet.
     *
     * @param  array  $languages  Array of language codes
     * @return int Total number of columns
     */
    public static function getTaxonomiesSheetColumnsCount(array $languages): int
    {
        // 2 (ID + Identifier) + languages count + 1 (Themes)
        return 2 + count($languages) + 1;
    }
}
