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
            return Action::danger('Please upload a valid file.');
        }

        try {
            $spreadsheet = $this->loadSpreadsheet($file);
            $worksheet = $spreadsheet->getActiveSheet();

            if (!$this->hasHeaders($worksheet)) {
                return Action::danger('The first row must contain column headers. Please read the instructions and check the file before trying again.');
            }

            if (!$this->hasValidData($worksheet)) {
                return Action::danger('The second row cannot be empty. Please read the instructions and check the file before trying again.');
            }


            $importer = new \App\Imports\EcPoiFromCSV();
            Excel::import($importer, $file);

            if (empty($importer->errors)) {
                return Action::message('File imported successfully.');
            }

            $this->processImportErrors($worksheet, $importer->errors);
            $updatedFile = $this->saveUpdatedSpreadsheet($spreadsheet);

            return Action::download(
                Storage::url('poi-file-errors.xlsx'),
                'poi-file-errors.xlsx'
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return Action::danger('Si è verificato un errore durante l\'elaborazione del file: ' . $e->getMessage());
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
        $errorColumn = null;
        $lastColumn = $worksheet->getHighestColumn(1);

        //search for the error column
        for ($col = 'A'; $col <= $lastColumn; $col++) {
            if ($worksheet->getCell($col . '1')->getValue() === self::ERROR_COLUMN_NAME) {
                $errorColumn = $col;
                break;
            }
        }

        //if the error column does not exist, create a new one
        if (!$errorColumn) {
            $errorColumn = ++$lastColumn;
            $worksheet->setCellValue($errorColumn . '1', self::ERROR_COLUMN_NAME);
        }

        //add the errors in the error column
        foreach ($errors as $error) {
            $worksheet->setCellValue($errorColumn . $error['row'], $error['message']);
            $this->highlightErrorRow($worksheet, $error['row'], $lastColumn);
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
        $range = "A{$row}:{$lastColumn}{$row}";
        $worksheet->getStyle($range)->getFill()
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
        // Get or create the reference sheet
        try {
            $referenceSheet = $spreadsheet->getSheetByName('Available References');
        } catch (\Exception $e) {
            $referenceSheet = $spreadsheet->createSheet();
            $referenceSheet->setTitle('Available References');
        }

        // Set the headers
        $referenceSheet->setCellValue('A1', 'Available POI Type Identifiers');
        $referenceSheet->setCellValue('B1', 'Available POI Theme Identifiers');
        $referenceSheet->getStyle('A1:B1')->getFont()->setBold(true);

        // Get the POI types and themes from the importer
        $importer = new \App\Imports\EcPoiFromCSV();
        $poiTypes = $importer->getPoiTypes();
        $poiThemes = $importer->getPoiThemes();

        // Insert the POI types and themes in the sheet
        $maxRows = max(count($poiTypes), count($poiThemes));
        for ($i = 0; $i < $maxRows; $i++) {
            if (isset($poiTypes[$i])) {
                $referenceSheet->setCellValue('A' . ($i + 2), $poiTypes[$i]);
            }
            if (isset($poiThemes[$i])) {
                $referenceSheet->setCellValue('B' . ($i + 2), $poiThemes[$i]);
            }
        }

        // Adjust the column width
        $referenceSheet->getColumnDimension('A')->setAutoSize(true);
        $referenceSheet->getColumnDimension('B')->setAutoSize(true);

        // Return to the first active sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Save the file
        $filePath = storage_path('app/public/poi-file-errors.xlsx');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filePath);

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
        $rules = $this->buildHelpText($validHeaders);

        return [
            File::make('Upload File', 'file')
                ->help('<strong> Read the instruction below </strong>' . '</br>' . '</br>' . $rules)
        ];
    }

    /**
     * Get valid headers from configuration.
     *
     * @return string Comma-separated list of valid headers
     */
    private function getValidHeaders(): string
    {
        $headers = array_filter(
            config('services.importers.ecPois.validHeaders'),
            fn($header) => $header !== self::ERROR_COLUMN_NAME
        );
        return implode(', ', $headers);
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
            'Please upload a valid .xlsx file.',
            '<strong>The first row should contain the headers.',
            'Starting from the second row, the file should contain pois data.',
            'The file must contain the following headers: ' . $validHeaders . '</strong>',
            'Please provide ID only if the poi already exist in the database.',
            '',
            'Mandatory fields are: <strong>name_it, poi_type (at least one, referenced by Geohub identifier), theme(at least one, referenced by Geohub identifier), lat, lng. (use "." to indicate float: 43.1234).</strong>',
            'Please use comma "," to separate multiple data in a column (eg. 2 different contact_phone).',
            'Please follow this example: <a href="' . asset('importer-examples/import-poi-example.xlsx') . '" target="_blank">Example</a>'
        ]);
    }
}
