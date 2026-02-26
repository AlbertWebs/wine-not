<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function array(): array
    {
        return [
            [
                'WINE-RED-001',
                'WINE-RED-001',
                '1234567890123',
                'Cabernet Sauvignon',
                'Optional description',
                'Existing Brand Name',
                'Wine',
                750,
                13.5,
                'France',
                800,
                1000,
                1500,
                20,
                5,
                'Aisle 1',
                'active',
            ],
            [
                '# Leave blank to keep existing when updating. Status: active or inactive.',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'part_number',
            'sku',
            'barcode',
            'name',
            'description',
            'brand',
            'category',
            'volume_ml',
            'alcohol_percentage',
            'country_of_origin',
            'cost_price',
            'min_price',
            'selling_price',
            'stock_quantity',
            'reorder_level',
            'location',
            'status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['color' => ['rgb' => '888888'], 'italic' => true]],
        ];
    }
}
