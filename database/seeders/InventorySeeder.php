<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;
use App\Models\Category;
use App\Models\Brand;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all()->keyBy('name');
        $brands = Brand::all();

        $products = [
            ['name' => 'Cabernet Sauvignon Red Wine', 'part_number' => 'WINE-RED-001', 'category' => 'Wine', 'brand' => 'Jacob\'s Creek', 'volume_ml' => 750, 'alcohol' => 13.5, 'country' => 'Australia', 'cost' => 800, 'min' => 1000, 'selling' => 1500, 'stock' => 48, 'reorder' => 15],
            ['name' => 'Chardonnay White Wine', 'part_number' => 'WINE-WHT-001', 'category' => 'Wine', 'brand' => 'Yellow Tail', 'volume_ml' => 750, 'alcohol' => 12.5, 'country' => 'Australia', 'cost' => 600, 'min' => 800, 'selling' => 1200, 'stock' => 60, 'reorder' => 20],
            ['name' => 'Brut Champagne', 'part_number' => 'CHAMP-001', 'category' => 'Champagne & Sparkling', 'brand' => 'Moët & Chandon', 'volume_ml' => 750, 'alcohol' => 12, 'country' => 'France', 'cost' => 4500, 'min' => 5500, 'selling' => 7500, 'stock' => 24, 'reorder' => 8],
            ['name' => 'Johnnie Walker Black Label', 'part_number' => 'WHISKY-001', 'category' => 'Whisky', 'brand' => 'Johnnie Walker', 'volume_ml' => 750, 'alcohol' => 40, 'country' => 'Scotland', 'cost' => 3500, 'min' => 4200, 'selling' => 5500, 'stock' => 30, 'reorder' => 10],
            ['name' => 'Jack Daniel\'s Tennessee Whiskey', 'part_number' => 'WHISKY-002', 'category' => 'Whisky', 'brand' => 'Jack Daniel\'s', 'volume_ml' => 750, 'alcohol' => 40, 'country' => 'USA', 'cost' => 3200, 'min' => 3800, 'selling' => 4800, 'stock' => 36, 'reorder' => 12],
            ['name' => 'Jameson Irish Whiskey', 'part_number' => 'WHISKY-003', 'category' => 'Whisky', 'brand' => 'Jameson', 'volume_ml' => 700, 'alcohol' => 40, 'country' => 'Ireland', 'cost' => 2800, 'min' => 3400, 'selling' => 4200, 'stock' => 42, 'reorder' => 14],
            ['name' => 'Absolut Vodka', 'part_number' => 'VODKA-001', 'category' => 'Vodka', 'brand' => 'Absolut', 'volume_ml' => 750, 'alcohol' => 40, 'country' => 'Sweden', 'cost' => 1200, 'min' => 1500, 'selling' => 2200, 'stock' => 72, 'reorder' => 24],
            ['name' => 'Smirnoff Red Label Vodka', 'part_number' => 'VODKA-002', 'category' => 'Vodka', 'brand' => 'Smirnoff', 'volume_ml' => 750, 'alcohol' => 37.5, 'country' => 'Russia', 'cost' => 900, 'min' => 1100, 'selling' => 1600, 'stock' => 84, 'reorder' => 28],
            ['name' => 'Tanqueray London Dry Gin', 'part_number' => 'GIN-001', 'category' => 'Gin', 'brand' => 'Tanqueray', 'volume_ml' => 750, 'alcohol' => 43.1, 'country' => 'UK', 'cost' => 2200, 'min' => 2700, 'selling' => 3500, 'stock' => 36, 'reorder' => 12],
            ['name' => 'Bombay Sapphire Gin', 'part_number' => 'GIN-002', 'category' => 'Gin', 'brand' => 'Bombay Sapphire', 'volume_ml' => 750, 'alcohol' => 40, 'country' => 'UK', 'cost' => 2400, 'min' => 2900, 'selling' => 3800, 'stock' => 30, 'reorder' => 10],
            ['name' => 'Bacardi Carta Blanca Rum', 'part_number' => 'RUM-001', 'category' => 'Rum', 'brand' => 'Bacardi', 'volume_ml' => 750, 'alcohol' => 37.5, 'country' => 'Puerto Rico', 'cost' => 1100, 'min' => 1400, 'selling' => 1900, 'stock' => 54, 'reorder' => 18],
            ['name' => 'Captain Morgan Spiced Rum', 'part_number' => 'RUM-002', 'category' => 'Rum', 'brand' => 'Captain Morgan', 'volume_ml' => 750, 'alcohol' => 35, 'country' => 'Jamaica', 'cost' => 1300, 'min' => 1600, 'selling' => 2100, 'stock' => 48, 'reorder' => 16],
            ['name' => 'Hennessy VS Cognac', 'part_number' => 'BRANDY-001', 'category' => 'Brandy', 'brand' => 'Hennessy', 'volume_ml' => 700, 'alcohol' => 40, 'country' => 'France', 'cost' => 4200, 'min' => 5000, 'selling' => 6500, 'stock' => 24, 'reorder' => 8],
            ['name' => 'Baileys Original Irish Cream', 'part_number' => 'LIQ-001', 'category' => 'Liqueur', 'brand' => 'Baileys', 'volume_ml' => 750, 'alcohol' => 17, 'country' => 'Ireland', 'cost' => 1800, 'min' => 2200, 'selling' => 2900, 'stock' => 42, 'reorder' => 14],
        ];

        $locations = ['A-1', 'A-2', 'B-1', 'B-2', 'C-1', 'C-2', 'D-1', 'E-1', 'F-1', 'G-1', 'H-1', 'I-1', 'J-1', 'K-1'];

        foreach ($products as $index => $p) {
            $category = $categories->get($p['category']);
            $brand = $brands->firstWhere('brand_name', $p['brand']);
            $barcode = 'WN' . str_pad($index + 1, 8, '0', STR_PAD_LEFT);
            while (Inventory::where('barcode', $barcode)->exists()) {
                $barcode = 'WN' . str_pad(++$index + 100, 8, '0', STR_PAD_LEFT);
            }

            Inventory::updateOrCreate(
                ['part_number' => $p['part_number']],
                [
                    'sku' => $p['part_number'],
                    'barcode' => $barcode,
                    'name' => $p['name'],
                    'description' => $p['name'] . ' – ' . ($p['volume_ml'] ?? 750) . ' ml',
                    'category_id' => $category?->id,
                    'brand_id' => $brand?->id,
                    'volume_ml' => $p['volume_ml'] ?? null,
                    'alcohol_percentage' => $p['alcohol'] ?? null,
                    'country_of_origin' => $p['country'] ?? null,
                    'cost_price' => $p['cost'],
                    'min_price' => $p['min'],
                    'selling_price' => $p['selling'],
                    'stock_quantity' => $p['stock'],
                    'reorder_level' => $p['reorder'],
                    'location' => $locations[$index % count($locations)],
                    'status' => 'active',
                ]
            );
        }
    }
}
