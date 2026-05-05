<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Throwable;

/**
 * Dopo geohub:apply-reer-from-workbook: aggiorna lo stesso workbook
 * marcando come "presente e aggiornato" le tracce che erano "presente da aggiornare"
 * e ricalcola i conteggi nel foglio Riepilogo.
 */
class ExportReerWorkbookPostApplyCommand extends Command
{
    private const SHEET_TRACKS = 'Tracce';

    private const SHEET_SUMMARY = 'Riepilogo';

    private const COL_STATUS_OLD = 'presente da aggiornare';

    private const COL_STATUS_NEW = 'presente e aggiornato';

    protected $signature = 'geohub:export-reer-workbook-post-apply
        {input : Percorso al .xlsx originale del confronto (es. storage/app/reer_report_workbook.xlsx)}
        {--output= : Percorso di uscita (default: storage/app/reer_report_post_apply_<data>.xlsx)}
        {--sheet=Tracce : Foglio delle tracce}
    ';

    protected $description = 'Produce una copia Excel del confronto REER con REER_CHECK aggiornato dopo l\'apply su GeoHub';

    public function handle(): int
    {
        $in = $this->resolvePath((string) $this->argument('input'));
        $sheetName = (string) $this->option('sheet');
        $defaultOut = storage_path('app/reer_report_post_apply_'.Carbon::now()->format('Ymd_His').'.xlsx');
        $out = $this->option('output') ? $this->resolvePath((string) $this->option('output')) : $defaultOut;

        if (! is_readable($in)) {
            $this->error("File non leggibile: {$in}");

            return 1;
        }

        try {
            $spreadsheet = IOFactory::load($in);
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (! $sheet) {
                $this->error("Foglio non trovato: {$sheetName}");

                return 1;
            }

            $changed = $this->updateTracksSheet($sheet);
            $this->updateSummarySheet($spreadsheet, $changed);

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($out);

            $this->info("Aggiornate {$changed} righe nel foglio \"{$sheetName}\".");
            $this->info('File salvato: '.$out);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }

        return 0;
    }

    private function resolvePath(string $path): string
    {
        if ($path !== '' && $path[0] === DIRECTORY_SEPARATOR) {
            return $path;
        }

        return base_path($path);
    }

    private function updateTracksSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): int
    {
        $rows = $sheet->toArray();
        if ($rows === []) {
            return 0;
        }
        $header = array_shift($rows);
        $colCheck = null;
        foreach ($header as $i => $label) {
            if (strtolower(trim((string) $label)) === 'reer_check') {
                $colCheck = (int) $i;
                break;
            }
        }
        if ($colCheck === null) {
            throw new \RuntimeException('Colonna REER_CHECK non trovata nel foglio tracce.');
        }

        $changed = 0;
        foreach ($rows as $idx => $row) {
            $raw = isset($row[$colCheck]) ? trim((string) $row[$colCheck]) : '';
            $norm = preg_replace('/[\s_-]+/u', ' ', strtolower($raw));
            if ($norm !== self::COL_STATUS_OLD) {
                continue;
            }
            $sheet->setCellValueByColumnAndRow($colCheck + 1, $idx + 2, self::COL_STATUS_NEW);
            $changed++;
        }

        return $changed;
    }

    private function updateSummarySheet(Spreadsheet $spreadsheet, int $changed): void
    {
        $summary = $spreadsheet->getSheetByName(self::SHEET_SUMMARY);
        if (! $summary || $changed <= 0) {
            return;
        }

        $dataRow = Carbon::now()->timezone(config('app.timezone'))->toIso8601String();
        $rows = $summary->toArray();
        foreach ($rows as $rowIdx => $row) {
            $key = isset($row[0]) ? trim((string) $row[0]) : '';
            if ($key === 'Data') {
                $summary->setCellValueByColumnAndRow(2, $rowIdx + 1, $dataRow);
            }
            if ($key === 'presente e aggiornato') {
                $prev = isset($row[1]) ? (int) $row[1] : 0;
                $summary->setCellValueByColumnAndRow(2, $rowIdx + 1, $prev + $changed);
            }
            if ($key === 'presente da aggiornare') {
                $prev = isset($row[1]) ? (int) $row[1] : 0;
                $summary->setCellValueByColumnAndRow(2, $rowIdx + 1, max(0, $prev - $changed));
            }
        }
    }
}
