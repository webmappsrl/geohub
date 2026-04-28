<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReerMatchingWorkbookExport implements WithMultipleSheets
{
    use Exportable;

    /** @var array<int, array<int, mixed>> */
    private $mainRows;

    /** @var array<int, array<int, mixed>> */
    private $summaryRows;

    /** @var array<int, array<int, mixed>> */
    private $geohubSenzaReerRows;

    /** @var array<int, array<int, mixed>> */
    private $reerSenzaGeohubRows;

    /** @var array<int, array<int, mixed>> */
    private $ambiguiRows;

    /**
     * @param  array<int, array<int, mixed>>  $mainRows
     * @param  array<int, array<int, mixed>>  $summaryRows
     * @param  array<int, array<int, mixed>>  $geohubSenzaReerRows
     * @param  array<int, array<int, mixed>>  $reerSenzaGeohubRows
     * @param  array<int, array<int, mixed>>  $ambiguiRows
     */
    public function __construct(
        array $mainRows,
        array $summaryRows,
        array $geohubSenzaReerRows,
        array $reerSenzaGeohubRows,
        array $ambiguiRows
    ) {
        $this->mainRows = $mainRows;
        $this->summaryRows = $summaryRows;
        $this->geohubSenzaReerRows = $geohubSenzaReerRows;
        $this->reerSenzaGeohubRows = $reerSenzaGeohubRows;
        $this->ambiguiRows = $ambiguiRows;
    }

    public function sheets(): array
    {
        return [
            new ReerNamedSheetExport('Tracce', ['ID_GEOHUB', 'LINK_EDIT_GEOHUB', 'REER_CHECK', 'REER_KMZ'], $this->mainRows),
            new ReerNamedSheetExport('Riepilogo', ['Parametro', 'Valore'], $this->summaryRows),
            new ReerNamedSheetExport('GeoHub_senza_REER', ['ID_GEOHUB', 'LINK_EDIT_GEOHUB'], $this->geohubSenzaReerRows),
            new ReerNamedSheetExport('REER_senza_GeoHub', ['ID_PERCORSO', 'REER_KMZ'], $this->reerSenzaGeohubRows),
            new ReerNamedSheetExport('Match_ambigui', ['ID_GEOHUB', 'LINK_EDIT_GEOHUB', 'candidati_entro_buffer'], $this->ambiguiRows),
        ];
    }
}

class ReerNamedSheetExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    /** @var string */
    private $title;

    /** @var array<int, string> */
    private $headings;

    /** @var array<int, array<int, mixed>> */
    private $rows;

    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function __construct(string $title, array $headings, array $rows)
    {
        $this->title = $title;
        $this->headings = $headings;
        $this->rows = $rows;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }
}
