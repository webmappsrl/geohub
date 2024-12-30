<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;


class UpdatedPoiFileExport implements WithMultipleSheets
{
    private $data;
    private $poiTypes;

    public function __construct(array $data, array $poiTypes)
    {
        $this->data = $data;
        $this->poiTypes = $poiTypes;
    }

    public function sheets(): array
    {
        return [
            new MainSheetExport($this->data),
            new PoiTypesSheetExport($this->poiTypes),
        ];
    }
}

class MainSheetExport implements FromCollection, WithHeadings
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return array_keys($this->data[0] ?? []);
    }
}

class PoiTypesSheetExport implements FromArray, WithTitle
{
    private $poiTypes;

    public function __construct(array $poiTypes)
    {
        $this->poiTypes = $poiTypes;
    }

    public function array(): array
    {
        return array_map(fn($type) => [$type], $this->poiTypes);
    }

    public function title(): string
    {
        return 'Poi Types';
    }
}
