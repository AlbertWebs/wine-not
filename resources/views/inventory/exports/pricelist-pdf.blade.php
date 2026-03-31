<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Wine Not Price List</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f3f4f6; font-weight: bold; }
        .header { text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Wine Not Price List</h2>
        <p>Generated on: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Part Number</th>
                <th>SKU</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Cost Price (KES)</th>
                <th>Min Price (KES)</th>
                <th>Selling Price (KES)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->part_number }}</td>
                    <td>{{ $item->sku ?? 'N/A' }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->category ? $item->category->name : 'N/A' }}</td>
                    <td>{{ $item->brand ? $item->brand->brand_name : 'N/A' }}</td>
                    <td>{{ number_format((float) $item->cost_price, 2) }}</td>
                    <td>{{ number_format((float) $item->min_price, 2) }}</td>
                    <td>{{ number_format((float) $item->selling_price, 2) }}</td>
                    <td>{{ ucfirst((string) $item->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

