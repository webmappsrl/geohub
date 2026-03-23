<?php

namespace App\Nova\Actions;

use App\Imports\EcPoiFromCSV;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\File;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UploadPoiFile extends PoiFileAction
{
    use InteractsWithQueue, Queueable;

    /**
     * Handle the action's execution.
     *
     * @param  ActionFields  $fields  The action fields containing the uploaded file
     * @param  Collection  $models  The collection of models being acted upon
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $file = $fields->file;

        if (! $this->isValidFile($file)) {
            return Action::danger(__('Please upload a valid file.'));
        }

        try {
            $spreadsheet = $this->loadSpreadsheet($file);
            $this->removeErrorsSheetIfPresent($spreadsheet);
            $worksheet = $spreadsheet->getActiveSheet();

            $fileHeadersNormalized = $this->getFileHeadersFromWorksheet($worksheet);
            $validHeaders = $this->getValidHeaders();
            $validHeadersOrdered = array_values($validHeaders);

            $structuralErrorRows = $this->buildStructuralErrorTable($validHeaders, $validHeadersOrdered, $fileHeadersNormalized);
            if (! empty($structuralErrorRows)) {
                $this->addErrorsSheet($spreadsheet, $structuralErrorRows);

                return $this->downloadUpdatedSpreadsheet($spreadsheet, true);
            }

            if (! $this->hasHeaders($worksheet)) {
                $this->addErrorsSheet($spreadsheet, [
                    [__('Type'), __('Detail')],
                    [__('File structure'), __('The first row must contain the column headers.')],
                ]);

                return $this->downloadUpdatedSpreadsheet($spreadsheet, true);
            }

            if (! $this->hasValidData($worksheet)) {
                $this->addErrorsSheet($spreadsheet, [
                    [__('Type'), __('Detail')],
                    [__('File structure'), __('The second row cannot be empty. Insert the POI data starting from the second row.')],
                ]);

                return $this->downloadUpdatedSpreadsheet($spreadsheet, true);
            }

            $importer = new EcPoiFromCSV;
            Excel::import($importer, $file);

            // Use sheet index 0 explicitly so the data sheet (with column "errors" and yellow highlighting) is the one we modify and save
            $dataSheet = $spreadsheet->getSheet(0);
            $this->processImportErrors($dataSheet, $importer->errors);
            $this->populatePoiIds($dataSheet, $importer->poiIds);

            if (! empty($importer->errors)) {
                $importErrorTable = $this->formatImportErrorsForSheet($importer->errors);
                $this->addErrorsSheet($spreadsheet, $importErrorTable);
            }

            return $this->downloadUpdatedSpreadsheet($spreadsheet, ! empty($importer->errors));
        } catch (\Throwable $e) {
            report($e);

            $serverErrorTable = $this->formatServerErrorForSheet($e);

            return $this->returnErrorFile($serverErrorTable);
        }
    }

    /**
     * Check if the uploaded file is valid.
     *
     * @param  mixed  $file  The uploaded file
     * @return bool Returns true if file is valid, false otherwise
     */
    private function isValidFile($file): bool
    {
        return ! empty($file);
    }

    /**
     * Create a minimal Excel file with only an "Errors" sheet and return Nova download response.
     *
     * @param  array<string>  $errorMessages  List of error messages (one per row in the sheet)
     * @return mixed Nova download response (Action::download returns array for JSON)
     */
    private function returnErrorFile(array $errorMessages)
    {
        $spreadsheet = new Spreadsheet;
        $this->addErrorsSheet($spreadsheet, $errorMessages);
        $spreadsheet->removeSheetByIndex(0);
        $filePath = storage_path('app/public/poi-file-updated.xlsx');
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($filePath);
        $fileName = 'poi-file-errors-'.now()->format('Y-m-d').'.xlsx';

        return Action::download(
            url('/download-poi-file/'.urlencode($fileName)),
            $fileName
        );
    }

    /**
     * Build table rows for structural errors (missing columns, wrong order).
     * Returns empty array if no structural errors; otherwise [headerRow, ...dataRows].
     *
     * @param  array  $validHeaders  Expected headers
     * @param  array  $validHeadersOrdered  Expected headers as ordered list
     * @param  array  $fileHeadersNormalized  Headers read from file (normalized)
     * @return array<int, array<int, string>> Table rows: first row = [Tipo, Dettaglio], then one row per error
     */
    private function buildStructuralErrorTable(array $validHeaders, array $validHeadersOrdered, array $fileHeadersNormalized): array
    {
        $rows = [];
        $missingColumns = array_diff($validHeaders, $fileHeadersNormalized);
        if (! empty($missingColumns)) {
            $rows[] = [__('Missing columns'), implode(', ', $missingColumns)];
        }

        $orderInFile = array_values(array_intersect($fileHeadersNormalized, $validHeaders));
        if ($orderInFile !== $validHeadersOrdered) {
            $rows[] = [
                __('Columns order'),
                __('The columns order is not correct.').' '.__('Expected order:').' '.implode(', ', $validHeadersOrdered),
            ];
        }

        if (empty($rows)) {
            return [];
        }

        return array_merge([[__('Type'), __('Detail')]], $rows);
    }

    /**
     * Build table rows for server errors in the "Errors" sheet. Only user-friendly messages
     * (no exception message, type or file/line) so the client is not shown technical details.
     *
     * @param  \Throwable  $e  Exception or Error (used for report(); sheet content is generic)
     * @return array<int, array<int, string>> Table rows: header [Tipo, Dettaglio] + 2 message rows
     */
    private function formatServerErrorForSheet(\Throwable $e): array
    {
        return [
            [__('Type'), __('Detail')],
            [__('Error'), __('An error occurred while processing the file.')],
            [__('Verification'), __('Verify that the file is in a valid Excel (.xlsx) format and that the structure is correct.')],
        ];
    }

    /**
     * Build table rows for per-row import errors. Columns: Riga | Motivo.
     *
     * @param  array<int, array{row: int|string, message: string}>  $importerErrors  Errors from importer
     * @return array<int, array<int, string|int>> Table rows: first row = [Riga, Motivo], then one row per error
     */
    private function formatImportErrorsForSheet(array $importerErrors): array
    {
        $rows = [[__('Row'), __('Reasons')]];
        foreach ($importerErrors as $err) {
            $rows[] = [$err['row'] ?? '', $err['message'] ?? ''];
        }

        return $rows;
    }

    /**
     * Remove the "Errors" sheet from the workbook if present (e.g. from a previous upload).
     * So when the user re-uploads a corrected file, old errors are discarded and only new ones are shown.
     */
    private function removeErrorsSheetIfPresent(Spreadsheet $spreadsheet): void
    {
        for ($i = 0; $i < $spreadsheet->getSheetCount(); $i++) {
            if ($spreadsheet->getSheet($i)->getTitle() === self::ERRORS_SHEET_TITLE) {
                $spreadsheet->removeSheetByIndex($i);
                break;
            }
        }
    }

    /**
     * Add the "Errors" worksheet with a table. First row = header (bold), rest = data.
     *
     * @param  Spreadsheet  $spreadsheet  The spreadsheet to modify
     * @param  array<int, array<int, string|int|float>>  $tableRows  Table rows: first row = headers, then data rows
     */
    private function addErrorsSheet(Spreadsheet $spreadsheet, array $tableRows): void
    {
        $errorsSheet = $spreadsheet->createSheet();
        $errorsSheet->setTitle(self::ERRORS_SHEET_TITLE);

        $maxCol = 0;
        foreach ($tableRows as $rowIndex => $row) {
            $colIndex = 1;
            foreach ($row as $cellValue) {
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                $errorsSheet->setCellValue($colLetter.($rowIndex + 1), $cellValue);
                $maxCol = max($maxCol, $colIndex);
                $colIndex++;
            }
        }

        $headerRange = 'A1:'.Coordinate::stringFromColumnIndex($maxCol).'1';
        $errorsSheet->getStyle($headerRange)->getFont()->setBold(true);

        for ($col = 1; $col <= $maxCol; $col++) {
            $errorsSheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }
    }

    /**
     * Get header names from the first row of the worksheet, normalized (trimmed, lowercase, spaces → underscore).
     * Normalization must match config headers (e.g. "name_it") so missing-column and order checks work.
     *
     * @param  Worksheet  $worksheet  The worksheet to read
     * @return array<int, string> List of header names in file order (normalized)
     */
    private function getFileHeadersFromWorksheet(Worksheet $worksheet): array
    {
        $headers = [];
        $lastColumn = $worksheet->getHighestColumn(1);
        if ($lastColumn === '') {
            return [];
        }
        for ($col = 'A'; $col <= $lastColumn; $col++) {
            $value = $worksheet->getCell($col.'1')->getValue();
            if ($value !== null && trim((string) $value) !== '') {
                $normalized = strtolower(trim((string) $value));
                $normalized = preg_replace('/\s+/', '_', $normalized);
                $headers[] = $normalized;
            }
        }

        return $headers;
    }

    /**
     * Save the spreadsheet to storage and return Nova download response.
     *
     * @param  Spreadsheet  $spreadsheet  The spreadsheet to save
     * @param  bool  $hasErrors  Whether the file contains import errors (affects filename)
     * @return mixed Nova download response (Action::download returns array for JSON)
     */
    private function downloadUpdatedSpreadsheet(Spreadsheet $spreadsheet, bool $hasErrors)
    {
        $this->saveUpdatedSpreadsheet($spreadsheet);
        $fileName = $this->determineFileName($hasErrors ? [['row' => 1]] : []);

        return Action::download(
            url('/download-poi-file/'.urlencode($fileName)),
            $fileName
        );
    }

    /**
     * Load the spreadsheet from the uploaded file.
     *
     * @param  mixed  $file  The uploaded file
     * @return Spreadsheet The loaded spreadsheet object
     */
    private function loadSpreadsheet($file): Spreadsheet
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        return $reader->load($file);
    }

    /**
     * Determine the file name based on the import errors.
     *
     * @param  array  $importerErrors  The import errors
     * @return string The file name
     */
    private function determineFileName(array $importerErrors): string
    {
        return ! empty($importerErrors) ? 'poi-file-errors-'.now()->format('Y-m-d').'.xlsx' : 'poi-file-imported-'.now()->format('Y-m-d').'.xlsx';
    }

    /**
     * Check if the worksheet has valid headers in the first row.
     *
     * @param  Worksheet  $worksheet  The worksheet to check
     * @return bool Returns true if headers are valid, false otherwise
     */
    private function hasHeaders(Worksheet $worksheet): bool
    {
        $lastColumn = $worksheet->getHighestColumn(1);

        for ($col = 'A'; $col <= $lastColumn; $col++) {
            if ($worksheet->getCell($col.'1')->getValue() !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the worksheet has valid data in the second row.
     *
     * @param  Worksheet  $worksheet  The worksheet to check
     * @return bool Returns true if data is valid, false otherwise
     */
    private function hasValidData(Worksheet $worksheet): bool
    {
        for ($col = 'B'; $col <= $worksheet->getHighestDataColumn(2); $col++) {

            if ($worksheet->getCell($col.'2')->getValue() !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process import errors and add them to the worksheet.
     *
     * @param  Worksheet  $worksheet  The worksheet to modify
     * @param  array  $errors  Array of error messages with row numbers
     */
    private function processImportErrors(Worksheet $worksheet, array $errors): void
    {
        $lastColumn = $worksheet->getHighestColumn(1);
        $errorColumn = $this->findOrCreateColumn($worksheet, self::ERROR_COLUMN_NAME, $lastColumn);
        $highestRow = $worksheet->getHighestRow();

        $this->clearPreviousErrors($worksheet, $errorColumn, $lastColumn, $highestRow, $errors);
        $this->addNewErrors($worksheet, $errorColumn, $lastColumn, $errors);
    }

    /**
     * Find or create a column for the given header.
     *
     * @param  Worksheet  $worksheet  The worksheet to modify
     * @param  string  $header  The header to find
     * @param  string  $lastColumn  The last column reference
     * @return string The column letter
     */
    private function findOrCreateColumn(Worksheet $worksheet, string $header, string &$lastColumn): string
    {
        $headerNormalized = strtolower(trim($header));
        for ($col = 'A'; $col <= $lastColumn; $col++) {
            $cellValue = $worksheet->getCell($col.'1')->getValue();
            $cellValue = is_scalar($cellValue) ? strtolower(trim((string) $cellValue)) : '';
            if ($cellValue === $headerNormalized) {
                return $col;
            }
        }

        $newColumn = ++$lastColumn;
        $worksheet->setCellValue($newColumn.'1', $header);

        return $newColumn;
    }

    /**
     * Clear previous errors and highlighting.
     *
     * @param  Worksheet  $worksheet  The worksheet to modify
     * @param  string  $errorColumn  The error column letter
     * @param  string  $lastColumn  The last column reference
     * @param  int  $highestRow  The highest row number
     * @param  array  $errors  Array of error messages with row numbers
     */
    private function clearPreviousErrors(Worksheet $worksheet, string $errorColumn, string $lastColumn, int $highestRow, array $errors): void
    {
        for ($row = 2; $row <= $highestRow; $row++) {
            if (! $this->hasError($row, $errors)) {
                $worksheet->setCellValue($errorColumn.$row, '');
                $worksheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()
                    ->setFillType(Fill::FILL_NONE);
            }
        }
    }

    /**
     * Check if a row has an error.
     *
     * @param  int  $row  The row number
     * @param  array  $errors  Array of error messages with row numbers
     * @return bool Returns true if the row has an error
     */
    private function hasError(int $row, array $errors): bool
    {
        foreach ($errors as $error) {
            if ($error['row'] == $row) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add new errors to the worksheet.
     *
     * @param  Worksheet  $worksheet  The worksheet to modify
     * @param  string  $errorColumn  The error column letter
     * @param  string  $lastColumn  The last column reference
     * @param  array  $errors  Array of error messages with row numbers
     */
    private function addNewErrors(Worksheet $worksheet, string $errorColumn, string $lastColumn, array $errors): void
    {
        foreach ($errors as $error) {
            $worksheet->setCellValue($errorColumn.$error['row'], $error['message']);
            $this->highlightErrorRow($worksheet, $error['row'], $lastColumn);
        }
    }

    /**
     * Populate POI IDs in the worksheet.
     *
     * @param  Worksheet  $worksheet  The worksheet to modify
     * @param  array<array{row: int|string, id: int|string}>  $poiIds  Array of POI IDs with row numbers
     */
    private function populatePoiIds(Worksheet $worksheet, array $poiIds): void
    {
        $lastColumn = $worksheet->getHighestColumn(1);
        $idColumn = $this->findOrCreateColumn($worksheet, 'id', $lastColumn);

        foreach ($poiIds as $poiId) {
            $worksheet->getCell($idColumn.$poiId['row'])
                ->setValueExplicit(
                    (string) $poiId['id'],
                    DataType::TYPE_STRING
                );
        }
    }

    /**
     * Highlight a row in the worksheet to indicate an error.
     *
     * @param  Worksheet  $worksheet  The worksheet to modify
     * @param  int|string  $row  The row number to highlight
     * @param  string  $lastColumn  The last column letter
     */
    private function highlightErrorRow(Worksheet $worksheet, $row, string $lastColumn): void
    {
        $worksheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB(self::ERROR_HIGHLIGHT_COLOR);
    }

    /**
     * Save the updated spreadsheet to storage.
     *
     * @param  Spreadsheet  $spreadsheet  The spreadsheet to save
     * @return string Returns the file path of the saved spreadsheet
     */
    private function saveUpdatedSpreadsheet(Spreadsheet $spreadsheet): string
    {
        $referenceSheet = $spreadsheet->getSheetByName(self::TAXONOMIES_SHEET_TITLE)
            ?? $spreadsheet->createSheet()->setTitle(self::TAXONOMIES_SHEET_TITLE);

        $taxonomiesData = $this->getTaxonomiesData();
        $header = self::buildTaxonomiesSheetHeader($taxonomiesData['languages']);

        $col = 1;
        foreach ($header as $headerValue) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $referenceSheet->setCellValue($columnLetter.'1', $headerValue);
            $col++;
        }

        $totalColumns = self::getTaxonomiesSheetColumnsCount($taxonomiesData['languages']);
        $lastColumn = Coordinate::stringFromColumnIndex($totalColumns);
        $referenceSheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);

        $dataRows = self::buildTaxonomiesSheetRows(
            $taxonomiesData['poiTypes'],
            $taxonomiesData['poiThemes'],
            $taxonomiesData['languages']
        );

        foreach ($dataRows as $index => $rowData) {
            $row = $index + 2;
            $col = 1;
            foreach ($rowData as $cellValue) {
                $columnLetter = Coordinate::stringFromColumnIndex($col);
                $referenceSheet->setCellValue($columnLetter.$row, $cellValue);
                $col++;
            }
        }

        for ($col = 1; $col <= $totalColumns; $col++) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $referenceSheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filePath = storage_path('app/public/poi-file-updated.xlsx');
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($filePath);

        return $filePath;
    }

    /**
     * Get the fields available on the action.
     *
     * @return array Array of Nova fields
     */
    public function fields(): array
    {
        $validHeaders = $this->getValidHeadersString();

        return [
            File::make('Upload File', 'file')
                ->help('<strong>'.__('Read the instruction below').'</strong>'.'</br>'.'</br>'.$this->buildHelpText($validHeaders)),
        ];
    }

    /**
     * Get valid headers from configuration as a comma-separated string.
     *
     * @return string Comma-separated list of valid headers
     */
    private function getValidHeadersString(): string
    {
        return implode(', ', parent::getValidHeaders());
    }

    /**
     * Build help text for the upload form.
     *
     * @param  string  $validHeaders  Comma-separated list of valid headers
     * @return string Returns formatted help text with HTML
     */
    private function buildHelpText(string $validHeaders): string
    {
        return implode('</br>', [
            __('Please upload a valid .xlsx file.'),
            '',
            '<strong>'.__('File Structure:').'</strong>',
            __('The file contains two sheets:'),
            '<strong>'.__('1. First Sheet (Main Data):').'</strong>',
            __('This sheet contains the POI data to be imported. The first row contains the column headers, and starting from the second row, the file should contain POI data.'),
            __('The file must contain the following headers: ').$validHeaders.'.',
            __('This sheet includes all the information about each POI: identification data (id, name, description), location data (lat, lng, address), contact information (phone, email), media (feature image, gallery), taxonomy references (poi_type, theme), and other optional fields.'),
            '',
            '<strong>'.__('How the First Sheet Works After Import:').'</strong>',
            __('After the import process, the system generates a new file with the same first sheet but with additional information:'),
            __('- Successfully imported POIs: The system automatically populates the "id" column with the database ID assigned to each POI that was imported successfully.'),
            __('- POIs with errors: If a POI cannot be imported due to validation errors or other issues, the entire row is highlighted in yellow and an "errors" column is added (or used if it already exists) containing a detailed error message explaining why the import failed.'),
            __('This allows you to easily identify which POIs were imported successfully (by checking the "id" column) and which ones need to be corrected (by checking the yellow highlighted rows and the "errors" column).'),
            __('You can then correct the errors in the file and re-upload it to import the remaining POIs.'),
            '',
            '<strong>'.__('2. Second Sheet (POI Types Taxonomies):').'</strong>',
            __('This sheet contains the reference data for POI types and themes. It includes:'),
            __('- POI Type ID: The unique identifier of each POI type'),
            __('- Available POI Type Identifiers: The Geohub identifiers that can be used in the main sheet'),
            __('- Available POI Type Names: The names of POI types in different languages (IT, EN, FR, etc.)'),
            __('- Available POI Theme Identifiers: The Geohub identifiers for themes that can be used in the main sheet'),
            __('This sheet serves as a reference guide to help you use the correct identifiers when importing POI data.'),
            '',
            '<strong>'.__('First Sheet Instructions:').'</strong>',
            __('The first row should contain the headers.'),
            __('Starting from the second row, the file should contain pois data.'),
            __('Please provide ID only if the poi already exist in the database.'),
            '',
            __('Mandatory fields are: ').'<strong>name_it, poi_type ('.__('at least one, referenced by Geohub identifier').'), theme('.__('at least one, referenced by Geohub identifier').'), lat, lng. ('.__('use "." to indicate float: 43.1234').').</strong>',
            __('Please use comma "," to separate multiple data in a column (eg. 2 different contact_phone).'),
            __('Please follow this example: ').'<a href="'.asset('importer-examples/import-poi-example.xlsx').'" target="_blank">'.__('Example').'</a>',
            __('If the import fails, the file will be downloaded with the errors highlighted.'),
            __('For more information, please check the ').'<a href="https://orchestrator.maphub.it/resources/documentations/48" target="_blank">'.__('documentation').'</a>',
        ]);
    }
}
