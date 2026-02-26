<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SeoSettings;

class SeoSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $seoSettings = [
            [
                'page_type' => 'homepage',
                'meta_title' => 'Wine Not | Wines & Spirits in Wangige',
                'meta_description' => 'Quality wines, whisky, vodka, gin, rum and more in Wangige. Your local wines & spirits selection. Fast delivery and competitive prices.',
                'meta_keywords' => 'wines, spirits, whisky, vodka, gin, Wangige, wine shop, liquor store, Wine Not',
                'og_title' => 'Wine Not | Wines & Spirits in Wangige',
                'og_description' => 'Quality wines and spirits in Wangige. Your local selection.',
                'og_image' => null,
                'structured_data' => json_encode([
                    '@context' => 'https://schema.org',
                    '@type' => 'LocalBusiness',
                    'name' => 'Wine Not',
                    'description' => 'Wines & spirits shop in Wangige. Quality selection of wines, whisky, vodka, gin, rum and more.',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => 'Wangige',
                        'addressRegion' => 'Kiambu',
                        'addressCountry' => 'KE',
                    ],
                    'areaServed' => [
                        '@type' => 'City',
                        'name' => 'Wangige',
                    ],
                ]),
                'custom_meta_tags' => '<meta name="geo.region" content="KE-20"><meta name="geo.placename" content="Wangige">',
            ],
            [
                'page_type' => 'products',
                'meta_title' => 'Wines & Spirits | Wine Not Wangige',
                'meta_description' => 'Browse our selection of wines and spirits in Wangige. Quality products from top brands. Categories and brands available.',
                'meta_keywords' => 'wines, spirits, Wangige, wine shop, liquor, Wine Not',
                'og_title' => 'Wines & Spirits | Wine Not Wangige',
                'og_description' => 'Browse our selection of wines and spirits in Wangige.',
                'og_image' => null,
                'structured_data' => null,
                'custom_meta_tags' => null,
            ],
            [
                'page_type' => 'product_detail',
                'meta_title' => '{product_name} | Wine Not Wangige | {brand_name}',
                'meta_description' => 'Buy {product_name} in Wangige. Quality wines & spirits from {brand_name}. SKU: {part_number}. Stock available. Fast delivery.',
                'meta_keywords' => '{product_name}, wines, spirits, Wangige, {category_name}, {brand_name}, Wine Not',
                'og_title' => '{product_name} | Wine Not Wangige',
                'og_description' => 'Buy {product_name} in Wangige. Quality wines & spirits. SKU: {part_number}.',
                'og_image' => null,
                'structured_data' => null,
                'custom_meta_tags' => null,
            ],
            [
                'page_type' => 'categories',
                'meta_title' => 'Wine & Spirit Categories | Wine Not Wangige',
                'meta_description' => 'Browse wines and spirits by category in Wangige. Wine, whisky, vodka, gin, rum and more. Quality products from top brands.',
                'meta_keywords' => 'wine categories, spirits categories, Wangige, Wine Not, wines by category',
                'og_title' => 'Wine & Spirit Categories | Wine Not Wangige',
                'og_description' => 'Browse wines and spirits by category in Wangige.',
                'og_image' => null,
                'structured_data' => null,
                'custom_meta_tags' => null,
            ],
            [
                'page_type' => 'category_detail',
                'meta_title' => '{category_name} | Wine Not Wangige',
                'meta_description' => 'Find {category_name} in Wangige. Quality wines and spirits. Browse our {category_name} collection. Top brands available.',
                'meta_keywords' => '{category_name}, wines, spirits, Wangige, Wine Not',
                'og_title' => '{category_name} | Wine Not Wangige',
                'og_description' => 'Find {category_name} in Wangige. Quality wines and spirits.',
                'og_image' => null,
                'structured_data' => null,
                'custom_meta_tags' => null,
            ],
            [
                'page_type' => 'brands',
                'meta_title' => 'Wine & Spirit Brands | Wine Not Wangige',
                'meta_description' => 'Browse wines and spirits by brand in Wangige. Quality products from top brands. All major brands available.',
                'meta_keywords' => 'wine brands, spirit brands, Wangige, Wine Not',
                'og_title' => 'Wine & Spirit Brands | Wine Not Wangige',
                'og_description' => 'Browse wines and spirits by brand in Wangige.',
                'og_image' => null,
                'structured_data' => null,
                'custom_meta_tags' => null,
            ],
            [
                'page_type' => 'brand_detail',
                'meta_title' => '{brand_name} | Wine Not Wangige',
                'meta_description' => 'Find {brand_name} wines and spirits in Wangige. Quality selection from {brand_name}. Browse our collection. Fast delivery available.',
                'meta_keywords' => '{brand_name}, wines, spirits, Wangige, Wine Not',
                'og_title' => '{brand_name} | Wine Not Wangige',
                'og_description' => 'Find {brand_name} wines and spirits in Wangige.',
                'og_image' => null,
                'structured_data' => null,
                'custom_meta_tags' => null,
            ],
            [
                'page_type' => 'vehicle_models',
                'meta_title' => 'Wines & Spirits | Wine Not Wangige',
                'meta_description' => 'Browse our selection of wines and spirits in Wangige. Quality products from top brands.',
                'meta_keywords' => 'wines, spirits, Wangige, Wine Not',
                'og_title' => 'Wines & Spirits | Wine Not Wangige',
                'og_description' => 'Browse our selection of wines and spirits in Wangige.',
                'og_image' => null,
                'structured_data' => null,
                'custom_meta_tags' => null,
            ],
            [
                'page_type' => 'vehicle_model_detail',
                'meta_title' => 'Wines & Spirits | Wine Not Wangige',
                'meta_description' => 'Browse our selection of wines and spirits in Wangige. Quality products from top brands.',
                'meta_keywords' => 'wines, spirits, Wangige, Wine Not',
                'og_title' => 'Wines & Spirits | Wine Not Wangige',
                'og_description' => 'Browse our selection of wines and spirits in Wangige.',
                'og_image' => null,
                'structured_data' => null,
                'custom_meta_tags' => null,
            ],
            [
                'page_type' => 'cart',
                'meta_title' => 'Shopping Cart | Wine Not Wangige',
                'meta_description' => 'Review your shopping cart at Wine Not. Wines and spirits ready for checkout.',
                'meta_keywords' => 'shopping cart, wines, spirits, Wangige, Wine Not',
                'og_title' => 'Shopping Cart | Wine Not Wangige',
                'og_description' => 'Review your shopping cart at Wine Not Wangige.',
                'og_image' => null,
                'structured_data' => null,
                'custom_meta_tags' => '<meta name="robots" content="noindex, follow">',
            ],
            [
                'page_type' => 'checkout',
                'meta_title' => 'Checkout | Wine Not Wangige',
                'meta_description' => 'Complete your order at Wine Not Wangige. Secure checkout with M-Pesa and cash on delivery options.',
                'meta_keywords' => 'checkout, wines, spirits, Wangige, order, Wine Not',
                'og_title' => 'Checkout | Wine Not Wangige',
                'og_description' => 'Complete your order at Wine Not Wangige.',
                'og_image' => null,
                'structured_data' => null,
                'custom_meta_tags' => '<meta name="robots" content="noindex, follow">',
            ],
        ];

        foreach ($seoSettings as $setting) {
            SeoSettings::updateOrCreate(
                ['page_type' => $setting['page_type']],
                $setting
            );
        }

        $this->command->info('SEO settings seeded successfully!');
    }
}
