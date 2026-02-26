<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template for inventory sheets with: Product name, QTY, Stockist Pricelist, Recommended resale, Distributor
 */
class InventorySimpleTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function array(): array
    {
        return [
            [
                'Cabernet Sauvignon 750ml',
                24,
                1200,
                1500,
                'Wines Distributor Ltd',
            ],
            [
                'Chardonnay 750ml',
                12,
                1100,
                1400,
                'Wines Distributor Ltd',
            ],
            [
                '# Product name = inventory name. QTY = stock. Stockist Pricelist = cost. Recommended resale = selling price. Distributor = brand.',
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
            'Product name',
            'QTY',
            'Stockist Pricelist',
            'Recommended resale',
            'Distributor',
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
