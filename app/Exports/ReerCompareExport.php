<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReerCompareExport implements FromArray, WithHeadings, ShouldAutoSize
{
    /**
     * @var array<int, array<int, mixed>>
     */
    private array $rows;

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return [
            'ID_GEOHUB',
            'LINK_EDIT_GEOHUB',
            'REER_CHECK',
            'REER_KMZ',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }
}

