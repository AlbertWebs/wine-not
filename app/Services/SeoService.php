<?php

namespace App\Services;

use App\Models\SeoSettings;
use Illuminate\Support\Facades\Cache;

class SeoService
{
    /**
     * Get SEO settings for a page type
     */
    public static function getSeoSettings($pageType, $dynamicData = [])
    {
        $cacheKey = "seo_settings_{$pageType}";
        
        $seoSettings = Cache::remember($cacheKey, 3600, function () use ($pageType) {
            return SeoSettings::where('page_type', $pageType)->first();
        });

        if (!$seoSettings) {
            // Return default SEO settings
            return self::getDefaultSeoSettings($pageType, $dynamicData);
        }

        // Replace dynamic placeholders
        $metaTitle = self::replacePlaceholders($seoSettings->meta_title ?? '', $dynamicData);
        $metaDescription = self::replacePlaceholders($seoSettings->meta_description ?? '', $dynamicData);
        $ogTitle = self::replacePlaceholders($seoSettings->og_title ?? $metaTitle, $dynamicData);
        $ogDescription = self::replacePlaceholders($seoSettings->og_description ?? $metaDescription, $dynamicData);

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => self::replacePlaceholders($seoSettings->meta_keywords ?? '', $dynamicData),
            'og_title' => $ogTitle,
            'og_description' => $ogDescription,
            'og_image' => $seoSettings->og_image ?? asset('images/default-og-image.jpg'),
            'structured_data' => $seoSettings->structured_data ?? null,
            'custom_meta_tags' => $seoSettings->custom_meta_tags ?? '',
        ];
    }

    /**
     * Get default SEO settings if none are configured
     */
    private static function getDefaultSeoSettings($pageType, $dynamicData = [])
    {
        $defaults = [
            'homepage' => [
                'meta_title' => 'Wine Not | Wines & Spirits',
                'meta_description' => 'Wine Not – quality wines and spirits. Browse red, white, champagne, whisky, vodka, gin, rum and more. Your local wines & spirits shop.',
                'meta_keywords' => 'wine, spirits, whisky, vodka, gin, rum, liquor, Wine Not',
            ],
            'products' => [
                'meta_title' => 'Our Drinks | Wine Not – Wines & Spirits',
                'meta_description' => 'Browse our wines and spirits. Red wine, white wine, champagne, whisky, vodka, gin, rum, brandy and liqueurs.',
                'meta_keywords' => 'wine, spirits, whisky, vodka, gin, rum, Wine Not',
            ],
            'product_detail' => [
                'meta_title' => '{product_name} | Wine Not',
                'meta_description' => 'Buy {product_name} at Wine Not. Quality wines and spirits. SKU: {part_number}.',
                'meta_keywords' => '{product_name}, wine, spirits, Wine Not, {category_name}, {brand_name}',
            ],
            'categories' => [
                'meta_title' => 'Categories | Wine Not – Wines & Spirits',
                'meta_description' => 'Browse wines and spirits by category. Wine, whisky, vodka, gin, rum, brandy, liqueur and more.',
                'meta_keywords' => 'wine categories, spirits, Wine Not',
            ],
            'category_detail' => [
                'meta_title' => '{category_name} | Wine Not',
                'meta_description' => 'Find {category_name} at Wine Not. Quality wines and spirits.',
                'meta_keywords' => '{category_name}, wine, spirits, Wine Not',
            ],
            'brands' => [
                'meta_title' => 'Brands | Wine Not – Wines & Spirits',
                'meta_description' => 'Browse wines and spirits by brand at Wine Not.',
                'meta_keywords' => 'wine brands, spirits brands, Wine Not',
            ],
            'brand_detail' => [
                'meta_title' => '{brand_name} | Wine Not',
                'meta_description' => 'Find {brand_name} at Wine Not. Quality wines and spirits.',
                'meta_keywords' => '{brand_name}, wine, spirits, Wine Not',
            ],
        ];

        $default = $defaults[$pageType] ?? $defaults['homepage'];
        
        return [
            'meta_title' => self::replacePlaceholders($default['meta_title'] ?? '', $dynamicData),
            'meta_description' => self::replacePlaceholders($default['meta_description'] ?? '', $dynamicData),
            'meta_keywords' => self::replacePlaceholders($default['meta_keywords'] ?? '', $dynamicData),
            'og_title' => self::replacePlaceholders($default['meta_title'] ?? '', $dynamicData),
            'og_description' => self::replacePlaceholders($default['meta_description'] ?? '', $dynamicData),
            'og_image' => asset('images/default-og-image.jpg'),
            'structured_data' => null,
            'custom_meta_tags' => '',
        ];
    }

    /**
     * Replace placeholders in SEO text
     */
    private static function replacePlaceholders($text, $data)
    {
        $placeholders = [
            '{product_name}' => $data['product_name'] ?? '',
            '{part_number}' => $data['part_number'] ?? '',
            '{category_name}' => $data['category_name'] ?? '',
            '{brand_name}' => $data['brand_name'] ?? '',
            '{make_name}' => $data['make_name'] ?? '',
            '{model_name}' => $data['model_name'] ?? '',
            '{price}' => isset($data['price']) ? 'KES ' . number_format($data['price'], 2) : '',
            '{company_name}' => $data['company_name'] ?? 'Wine Not',
        ];

        foreach ($placeholders as $placeholder => $value) {
            $text = str_replace($placeholder, $value, $text);
        }

        return $text;
    }

    /**
     * Generate structured data for a product
     */
    public static function generateProductStructuredData($product, $settings = [])
    {
        $companyName = $settings['company_name'] ?? 'Wine Not';
        $siteUrl = url('/');
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->description ?? $product->name,
            'sku' => $product->part_number,
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->brand->brand_name ?? 'Unknown',
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => $product->selling_price,
                'priceCurrency' => 'KES',
                'availability' => $product->stock_quantity > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'seller' => [
                    '@type' => 'LocalBusiness',
                    'name' => $companyName,
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => 'Wangige',
                        'addressCountry' => 'KE',
                    ],
                ],
            ],
            'image' => $product->image ? asset('storage/' . $product->image) : null,
        ];
    }

    /**
     * Generate structured data for LocalBusiness
     */
    public static function generateLocalBusinessStructuredData($settings = [])
    {
        $companyName = $settings['company_name'] ?? 'Wine Not';
        $phone = $settings['phone'] ?? '';
        $email = $settings['email'] ?? '';
        $address = $settings['address'] ?? 'Wangige, Kenya';
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $companyName,
            'description' => 'Wines & spirits shop in Wangige. Quality selection of wines, whisky, vodka, gin, rum and more.',
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Wangige',
                'addressRegion' => 'Kiambu',
                'addressCountry' => 'KE',
                'streetAddress' => $address,
            ],
            'telephone' => $phone,
            'email' => $email,
            'url' => url('/'),
            'priceRange' => '$$',
            'areaServed' => [
                '@type' => 'City',
                'name' => 'Wangige',
            ],
        ];
    }
}

