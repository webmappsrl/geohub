<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Actions\Action;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Laravel\Nova\Fields\ActionFields;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UploadPoiFile extends Action
{
    use InteractsWithQueue, Queueable;

    private const ERROR_COLUMN_NAME = 'errors';
    private const ERROR_HIGHLIGHT_COLOR = 'FFFF00';

    /**
     * Handle the action's execution.
     *
     * @param ActionFields $fields The action fields containing the uploaded file
     * @param Collection $models The collection of models being acted upon
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $file = $fields->file;

        if (!$this->isValidFile($file)) {
            return Action::danger(__('Please upload a valid file.'));
        }

        try {
            $spreadsheet = $this->loadSpreadsheet($file);
            $worksheet = $spreadsheet->getActiveSheet();

            if (!$this->hasHeaders($worksheet)) {
                return Action::danger(__('The first row must contain column headers. Please read the instructions and check the file before trying again.'));
            }

            if (!$this->hasValidData($worksheet)) {
                return Action::danger(__('The second row cannot be empty. Please read the instructions and check the file before trying again.'));
            }

            $importer = new \App\Imports\EcPoiFromCSV();
            Excel::import($importer, $file);

            $this->processImportErrors($worksheet, $importer->errors);
            $this->populatePoiIds($worksheet, $importer->poiIds);

            $filePath = $this->saveUpdatedSpreadsheet($spreadsheet);

            return Action::download(
                Storage::url('poi-file-updated.xlsx'),
                $this->determineFileName($importer->errors)
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return Action::danger(__('Si è verificato un errore durante l\'elaborazione del file: ') . $e->getMessage());
        }
    }

    /**
     * Check if the uploaded file is valid.
     *
     * @param mixed $file The uploaded file
     * @return bool Returns true if file is valid, false otherwise
     */
    private function isValidFile($file): bool
    {
        return !empty($file);
    }

    /**
     * Load the spreadsheet from the uploaded file.
     *
     * @param mixed $file The uploaded file
     * @return Spreadsheet The loaded spreadsheet object
     */
    private function loadSpreadsheet($file): Spreadsheet
    {
        return IOFactory::load($file);
    }

    /**
     * Determine the file name based on the import errors.
     *
     * @param array $importerErrors The import errors
     * @return string The file name
     */
    private function determineFileName(array $importerErrors): string
    {
        return !empty($importerErrors) ? 'poi-file-errors-' . now()->format('Y-m-d') . '.xlsx' : 'poi-file-imported-' . now()->format('Y-m-d') . '.xlsx';
    }

    /**
     * Check if the worksheet has valid headers in the first row.
     *
     * @param Worksheet $worksheet The worksheet to check
     * @return bool Returns true if headers are valid, false otherwise
     */
    private function hasHeaders(Worksheet $worksheet): bool
    {
        $lastColumn = $worksheet->getHighestColumn(1);

        for ($col = 'A'; $col <= $lastColumn; $col++) {
            if ($worksheet->getCell($col . '1')->getValue() !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the worksheet has valid data in the second row.
     *
     * @param Worksheet $worksheet The worksheet to check
     * @return bool Returns true if data is valid, false otherwise
     */
    private function hasValidData(Worksheet $worksheet): bool
    {
        for ($col = 'B'; $col <= $worksheet->getHighestColumn(2); $col++) {
            if ($worksheet->getCell($col . '2')->getValue() !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process import errors and add them to the worksheet.
     *
     * @param Worksheet $worksheet The worksheet to modify
     * @param array $errors Array of error messages with row numbers
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
     * @param Worksheet $worksheet The worksheet to modify
     * @param string $header The header to find
     * @param string $lastColumn The last column reference
     * @return string The column letter
     */
    private function findOrCreateColumn(Worksheet $worksheet, string $header, string &$lastColumn): string
    {
        for ($col = 'A'; $col <= $lastColumn; $col++) {
            if ($worksheet->getCell($col . '1')->getValue() === $header) {
                return $col;
            }
        }

        $newColumn = ++$lastColumn;
        $worksheet->setCellValue($newColumn . '1', $header);
        return $newColumn;
    }

    /**
     * Clear previous errors and highlighting.
     *
     * @param Worksheet $worksheet The worksheet to modify
     * @param string $errorColumn The error column letter
     * @param string $lastColumn The last column reference
     * @param int $highestRow The highest row number
     * @param array $errors Array of error messages with row numbers
     */
    private function clearPreviousErrors(Worksheet $worksheet, string $errorColumn, string $lastColumn, int $highestRow, array $errors): void
    {
        for ($row = 2; $row <= $highestRow; $row++) {
            if (!$this->hasError($row, $errors)) {
                $worksheet->setCellValue($errorColumn . $row, '');
                $worksheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE);
            }
        }
    }

    /**
     * Check if a row has an error.
     *
     * @param int $row The row number
     * @param array $errors Array of error messages with row numbers
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
     * @param Worksheet $worksheet The worksheet to modify
     * @param string $errorColumn The error column letter
     * @param string $lastColumn The last column reference
     * @param array $errors Array of error messages with row numbers
     */
    private function addNewErrors(Worksheet $worksheet, string $errorColumn, string $lastColumn, array $errors): void
    {
        foreach ($errors as $error) {
            $worksheet->setCellValue($errorColumn . $error['row'], $error['message']);
            $this->highlightErrorRow($worksheet, $error['row'], $lastColumn);
        }
    }

    /**
     * Populate POI IDs in the worksheet.
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet The worksheet to modify
     * @param array<array{row: int|string, id: int|string}> $poiIds Array of POI IDs with row numbers
     * @return void
     */
    private function populatePoiIds(Worksheet $worksheet, array $poiIds): void
    {
        $lastColumn = $worksheet->getHighestColumn(1);
        $idColumn = $this->findOrCreateColumn($worksheet, 'id', $lastColumn);

        foreach ($poiIds as $poiId) {
            $worksheet->setCellValue($idColumn . $poiId['row'], $poiId['id']);
        }
    }

    /**
     * Highlight a row in the worksheet to indicate an error.
     *
     * @param Worksheet $worksheet The worksheet to modify
     * @param int|string $row The row number to highlight
     * @param string $lastColumn The last column letter
     */
    private function highlightErrorRow(Worksheet $worksheet, $row, string $lastColumn): void
    {
        $worksheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB(self::ERROR_HIGHLIGHT_COLOR);
    }

    /**
     * Save the updated spreadsheet to storage.
     *
     * @param Spreadsheet $spreadsheet The spreadsheet to save
     * @return string Returns the file path of the saved spreadsheet
     */
    private function saveUpdatedSpreadsheet(Spreadsheet $spreadsheet): string
    {
        $referenceSheet = $spreadsheet->getSheetByName('Available References')
            ?? $spreadsheet->createSheet()->setTitle('Available References');

        $referenceSheet->setCellValue('A1', 'Available POI Type Identifiers')
            ->setCellValue('B1', 'Available POI Theme Identifiers')
            ->getStyle('A1:B1')->getFont()->setBold(true);

        $importer = new \App\Imports\EcPoiFromCSV();
        $maxRows = max(count($importer->poiTypes), count($importer->poiThemes));

        for ($i = 0; $i < $maxRows; $i++) {
            if (isset($importer->poiTypes[$i])) {
                $referenceSheet->setCellValue('A' . ($i + 2), $importer->poiTypes[$i]);
            }
            if (isset($importer->poiThemes[$i])) {
                $referenceSheet->setCellValue('B' . ($i + 2), $importer->poiThemes[$i]);
            }
        }

        $referenceSheet->getColumnDimension('A')->setAutoSize(true);
        $referenceSheet->getColumnDimension('B')->setAutoSize(true);

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
        $validHeaders = $this->getValidHeaders();

        return [
            File::make('Upload File', 'file')
                ->help('<strong>' . __('Read the instruction below') . '</strong>' . '</br>' . '</br>' . $this->buildHelpText($validHeaders))
        ];
    }

    /**
     * Get valid headers from configuration.
     *
     * @return string Comma-separated list of valid headers
     */
    private function getValidHeaders(): string
    {
        return implode(', ', array_filter(
            config('services.importers.ecPois.validHeaders'),
            fn($header) => $header !== self::ERROR_COLUMN_NAME
        ));
    }

    /**
     * Build help text for the upload form.
     *
     * @param string $validHeaders Comma-separated list of valid headers
     * @return string Returns formatted help text with HTML
     */
    private function buildHelpText(string $validHeaders): string
    {
        return implode('</br>', [
            __('Please upload a valid .xlsx file.'),
            '<strong>' . __('The first row should contain the headers.'),
            __('Starting from the second row, the file should contain pois data.'),
            __('The file must contain the following headers: ') . $validHeaders . '</strong>',
            __('Please provide ID only if the poi already exist in the database.'),
            '',
            __('Mandatory fields are: ') . '<strong>name_it, poi_type (' . __('at least one, referenced by Geohub identifier') . '), theme(' . __('at least one, referenced by Geohub identifier') . '), lat, lng. (' . __('use "." to indicate float: 43.1234') . ').</strong>',
            __('Please use comma "," to separate multiple data in a column (eg. 2 different contact_phone).'),
            __('Please follow this example: ') . '<a href="' . asset('importer-examples/import-poi-example.xlsx') . '" target="_blank">' . __('Example') . '</a>',
            __('If the import fails, the file will be downloaded with the errors highlighted.'),
            __('For more information, please check the ') . '<a href="https://orchestrator.maphub.it/resources/documentations/48" target="_blank">' . __('documentation') . '</a>'
        ]);
    }
}
