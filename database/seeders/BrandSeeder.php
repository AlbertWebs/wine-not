<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;
use App\Models\Inventory;

class BrandSeeder extends Seeder
{
    /** Legacy car/auto brands to remove when seeding wine & spirits. */
    private const LEGACY_CAR_BRANDS = [
        // Vehicle makes (often seeded as brands)
        'Toyota', 'Nissan', 'Mazda', 'Subaru', 'Mitsubishi', 'Suzuki', 'Isuzu', 'Honda', 'Ford',
        'Mercedes-Benz', 'BMW', 'Volkswagen', 'Peugeot', 'Hyundai', 'Kia', 'Land Rover', 'Audi',
        'Volvo', 'Jeep', 'Daihatsu', 'Lexus', 'Chevrolet', 'Renault', 'Tata', 'Mahindra', 'Hino',
        'Fuso', 'Scania', 'MAN', 'UD Trucks', 'Porsche',
        // Auto parts brands (from KenyaVehicleDataSeeder etc.)
        'Bosch', 'Denso', 'NGK', 'Mann Filter', 'Mobil', 'Castrol', 'Delphi', 'Valeo', 'Continental',
        'TRW', 'Brembo', 'Monroe', 'KYB', 'Gates', 'Mahle', 'Hella', 'Philips', 'Osram', 'ACDelco',
        'Mopar', 'Motorcraft', 'Beck Arnley', 'Wix', 'Fram', 'K&N', 'Meyle', 'Febi', 'Lemforder',
        'Magneti Marelli', 'Pierburg', 'Sachs', 'Luk', 'ZF', 'SKF', 'Timken', 'Asimco', 'NZE',
        'AllParts', 'RBI', 'Jinbo', 'URW', 'Exedy', 'Bilstein', 'Aisin', 'Ferodo', 'Sumitomo',
        'Toyo', 'Bridgestone', 'Pirelli', 'Goodyear', 'Michelin', 'Nissin', 'Champion', 'Koyo', 'Dayco',
    ];

    public function run(): void
    {
        $brands = [
            ['brand_name' => 'Jacob\'s Creek', 'country' => 'Australia'],
            ['brand_name' => 'Yellow Tail', 'country' => 'Australia'],
            ['brand_name' => 'Concha y Toro', 'country' => 'Chile'],
            ['brand_name' => 'Barefoot', 'country' => 'USA'],
            ['brand_name' => 'Moët & Chandon', 'country' => 'France'],
            ['brand_name' => 'Johnnie Walker', 'country' => 'Scotland'],
            ['brand_name' => 'Jack Daniel\'s', 'country' => 'USA'],
            ['brand_name' => 'Jameson', 'country' => 'Ireland'],
            ['brand_name' => 'Glenfiddich', 'country' => 'Scotland'],
            ['brand_name' => 'Absolut', 'country' => 'Sweden'],
            ['brand_name' => 'Smirnoff', 'country' => 'Russia'],
            ['brand_name' => 'Grey Goose', 'country' => 'France'],
            ['brand_name' => 'Tanqueray', 'country' => 'UK'],
            ['brand_name' => 'Bombay Sapphire', 'country' => 'UK'],
            ['brand_name' => 'Hendrick\'s', 'country' => 'Scotland'],
            ['brand_name' => 'Bacardi', 'country' => 'Puerto Rico'],
            ['brand_name' => 'Captain Morgan', 'country' => 'Jamaica'],
            ['brand_name' => 'Hennessy', 'country' => 'France'],
            ['brand_name' => 'Rémy Martin', 'country' => 'France'],
            ['brand_name' => 'Baileys', 'country' => 'Ireland'],
            ['brand_name' => 'Kahlúa', 'country' => 'Mexico'],
            ['brand_name' => 'Patrón', 'country' => 'Mexico'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['brand_name' => $brand['brand_name']],
                $brand
            );
        }

        $fallback = Brand::where('brand_name', "Jacob's Creek")->first();
        if ($fallback) {
            foreach (self::LEGACY_CAR_BRANDS as $name) {
                $old = Brand::where('brand_name', $name)->first();
                if ($old) {
                    Inventory::where('brand_id', $old->id)->update(['brand_id' => $fallback->id]);
                    $old->delete();
                }
            }
        }
    }
}
