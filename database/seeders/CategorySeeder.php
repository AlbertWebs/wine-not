<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Inventory;

class CategorySeeder extends Seeder
{
    /** Old automotive category names to remove when seeding wine/spirits. */
    private const LEGACY_AUTO_NAMES = [
        'Engine Parts', 'Brake System', 'Suspension', 'Electrical', 'Cooling System',
        'Transmission', 'Exhaust System', 'Filters', 'Belts & Hoses', 'Lights & Bulbs',
        'Body Parts', 'Interior Parts', 'Wheels & Tires', 'Steering', 'Fuel System',
        'Ignition System', 'Oil & Fluids', 'Gaskets & Seals', 'Sensors', 'Clutch System',
        'Drive Shaft', 'Timing', 'Valve Train', 'Pistons & Rings', 'Camshaft', 'Crankshaft',
        'Oil Pump', 'Water Pump', 'Thermostat', 'Radiator', 'AC Components', 'Wiper System',
        'Mirrors', 'Weatherstripping', 'Fasteners',
    ];

    public function run(): void
    {
        $categories = [
            ['name' => 'Wine', 'description' => 'Red, white, rosé, and fortified wines'],
            ['name' => 'Whisky', 'description' => 'Scotch, bourbon, Irish, and world whiskies'],
            ['name' => 'Vodka', 'description' => 'Vodka and flavoured vodka'],
            ['name' => 'Gin', 'description' => 'London dry, flavoured, and craft gins'],
            ['name' => 'Rum', 'description' => 'White, dark, spiced, and premium rums'],
            ['name' => 'Brandy', 'description' => 'Cognac, armagnac, and fruit brandies'],
            ['name' => 'Liqueur', 'description' => 'Cream liqueurs, fruit liqueurs, and digestifs'],
            ['name' => 'Champagne & Sparkling', 'description' => 'Champagne, prosecco, cava, and sparkling wines'],
            ['name' => 'Other', 'description' => 'Tequila, mezcal, sake, and other spirits'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }

        $other = Category::where('name', 'Other')->first();
        if ($other) {
            foreach (self::LEGACY_AUTO_NAMES as $name) {
                $old = Category::where('name', $name)->first();
                if ($old) {
                    Inventory::where('category_id', $old->id)->update(['category_id' => $other->id]);
                    $old->delete();
                }
            }
        }
    }
}
