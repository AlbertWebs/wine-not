<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PriceListExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        return [
            'Part Number',
            'SKU',
            'Product Name',
            'Category',
            'Brand',
            'Cost Price (KES)',
            'Min Price (KES)',
            'Selling Price (KES)',
            'Status',
        ];
    }

    public function map($item): array
    {
        return [
            $item->part_number,
            $item->sku ?? 'N/A',
            $item->name,
            $item->category ? $item->category->name : 'N/A',
            $item->brand ? $item->brand->brand_name : 'N/A',
            number_format((float) $item->cost_price, 2),
            number_format((float) $item->min_price, 2),
            number_format((float) $item->selling_price, 2),
            ucfirst((string) $item->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

