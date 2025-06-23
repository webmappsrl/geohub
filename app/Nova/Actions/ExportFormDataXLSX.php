<?php

namespace App\Nova\Actions;

use App\Models\App;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportFormDataXLSX extends Action
{
    protected $type;

    public function __construct($type = 'ugc_pois')
    {
        $this->type = $type;
        $this->onlyOnIndex();
    }

    public function name()
    {
        return __('Export All Form Data');
    }

    protected function getDownloadUrl(string $filePath): string
    {
        return URL::temporarySignedRoute('laravel-nova-excel.download', now()->addMinutes(1), [
            'path' => encrypt($filePath),
            'filename' => $this->type.'.xlsx',
        ]);
    }

    public function handle(ActionFields $fields, Collection $models): array
    {
        $modelsGoupedByApp = $models->groupBy('sku');
        $sheets = [];
        foreach ($modelsGoupedByApp as $appId => $modelsInGroup) {

            $app = App::where('sku', $appId)->first();
            $acquisition_form = $this->type === 'ugc_pois' ? $app->poi_acquisition_form : $app->track_acquisition_form;
            $formSchemas = json_decode($acquisition_form, true);
            $dataSheets = [];
            $heading = [];
            foreach ($formSchemas as $formSchema) {
                $allFieldLabels = [$this->type, 'username', 'created_at', 'geometry'];
                $allFieldNames = [$this->type, 'username', 'created_at', 'geometry'];
                $fieldSchemas = [];
                foreach ($formSchema['fields'] as $field) {
                    $label = reset($field['label']);
                    $allFieldLabels[] = $label;
                    $allFieldNames[] = $field['name'];
                    $fieldSchemas[$field['name']] = $field;
                    if ($field['type'] === 'select') {
                        $allFieldLabels[] = $label.' (valore)';
                        $allFieldNames[] = $field['name'].'_select_val';
                    }
                }
                if (isset($formSchema['id'])) {
                    $heading[$formSchema['id']][] = $allFieldNames;
                    $dataSheets[$formSchema['id']][] = $allFieldNames;
                    $dataSheets[$formSchema['id']][] = $allFieldLabels;
                    foreach ($modelsInGroup as $model) {
                        $formData = $model->properties['form'] ?? $model->properties;
                        $ugc_path = $this->type === 'ugc_pois' ? 'ugc-pois' : 'ugc-tracks';
                        $formData[$this->type] = url('/resources/'.$ugc_path.'/'.$model['id']);
                        $formData['username'] = $model->user->name;
                        $formData['created_at'] = $model->created_at ? $model->created_at->format('Y-m-d H:i:s') : 'N/A';

                        if ($model->geometry) {
                            $geojson = $model->getGeojson();
                            if ($geojson && isset($geojson['geometry']['coordinates'])) {
                                $formData['geometry'] = json_encode($geojson['geometry']['coordinates']);
                            } else {
                                $formData['geometry'] = 'N/A';
                            }
                        } else {
                            $formData['geometry'] = 'N/A';
                        }

                        if (isset($formData['id']) && $formSchema['id'] === $formData['id']) {
                            foreach ($heading[$formData['id']] as $headingRow) {
                                foreach ($headingRow as $fieldName) {
                                    $rowData[$fieldName] = $this->getFieldValue($fieldName, $fieldSchemas, $formData);
                                }
                            }
                            $dataSheets[$formData['id']][] = $rowData;
                        }
                    }
                }
            }
        }

        foreach ($dataSheets as $id => $dataSheet) {
            $sheets[] = new Sheet($dataSheet, $id);
        }
        $response = Excel::download(
            new MultiSheetExport($sheets),
            'all-form-data.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );

        return action::download($this->getDownloadUrl($response->getFile()->getPathname()), $this->type.'.xlsx');
    }

    public function fields()
    {
        return [];
    }

    private function getFieldValue(string $fieldName, array $fieldSchemas, array $formData): string
    {
        if (! $this->isValueField($fieldName)) {
            return $formData[$fieldName] ?? 'N/A';
        }

        $originalFieldName = $this->getOriginalFieldName($fieldName);

        if (! $this->isValidSelectField($originalFieldName, $fieldSchemas, $formData)) {
            return 'N/A';
        }

        return $this->getSelectFieldLabel($originalFieldName, $fieldSchemas, $formData);
    }

    private function isValueField(string $fieldName): bool
    {
        return str_ends_with($fieldName, '_select_val');
    }

    private function getOriginalFieldName(string $fieldName): string
    {
        return str_replace('_select_val', '', $fieldName);
    }

    private function isValidSelectField(string $fieldName, array $fieldSchemas, array $formData): bool
    {
        return isset($fieldSchemas[$fieldName]) &&
            $fieldSchemas[$fieldName]['type'] === 'select' &&
            isset($formData[$fieldName]);
    }

    private function getSelectFieldLabel(string $fieldName, array $fieldSchemas, array $formData): string
    {
        $selectedValue = $formData[$fieldName];

        foreach ($fieldSchemas[$fieldName]['values'] as $option) {
            if ($option['value'] === $selectedValue) {
                return reset($option['label']);
            }
        }

        return 'N/A';
    }
}
class Sheet implements FromArray, WithStyles, WithTitle
{
    protected $data;

    protected $title;

    public function __construct($data, $title)
    {
        $this->data = $data;
        $this->title = $title;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet)
    {
        // Assume that the URL is in the first column of each row
        $highestRow = $sheet->getHighestRow();
        for ($row = 3; $row <= $highestRow; $row++) {
            $cellCoordinate = 'A'.$row;
            $url = $sheet->getCell($cellCoordinate)->getValue();
            $sheet->getCell($cellCoordinate)->getHyperlink()->setUrl($url);
            $sheet->getStyle($cellCoordinate)->getFont()->setUnderline(true)->setColor(new Color(Color::COLOR_BLUE));
        }
    }
}
class MultiSheetExport implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    public function __construct($sheets)
    {
        $this->sheets = $sheets;
    }

    public function sheets(): array
    {
        return $this->sheets;
    }
}
