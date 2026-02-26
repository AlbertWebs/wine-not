<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'part_number',
        'sku',
        'barcode',
        'name',
        'description',
        'image',
        'brand_id',
        'category_id',
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

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'min_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'reorder_level' => 'integer',
            'volume_ml' => 'integer',
            'alcohol_percentage' => 'decimal:2',
        ];
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'part_id');
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'part_id');
    }

    public function priceHistories()
    {
        return $this->hasMany(PriceHistory::class, 'part_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'reorder_level');
    }

    public function isLowStock()
    {
        return $this->stock_quantity <= $this->reorder_level;
    }

    /**
     * Generate a unique SKU/Product Code (part_number) when not provided.
     * Format: WN-000001, WN-000002, ...
     */
    public static function generatePartNumber(): string
    {
        $prefix = 'WN-';
        $existing = self::where('part_number', 'like', $prefix . '%')->pluck('part_number');
        $max = 0;
        foreach ($existing as $code) {
            if (preg_match('/^WN-(\d+)$/', $code, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }
        $next = $max + 1;
        $partNumber = $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        $original = $partNumber;
        $counter = 0;
        while (self::where('part_number', $partNumber)->exists()) {
            $counter++;
            $partNumber = $original . '-' . $counter;
        }

        return $partNumber;
    }

    public static function generateSku(array $data): string
    {
        $categoryPrefix = '';
        if (!empty($data['category_id'])) {
            $category = Category::find($data['category_id']);
            if ($category) {
                $cleanedName = preg_replace('/[^A-Za-z0-9]/', '', $category->name);
                $categoryPrefix = strtoupper(substr($cleanedName, 0, 3));
            }
        }

        $partNumberBase = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $data['part_number'] ?? ''));
        $timestamp = now()->format('Ymd');

        $sku = ($categoryPrefix ? $categoryPrefix . '-' : '') . ($partNumberBase ?: 'ITEM') . '-' . $timestamp;

        $counter = 1;
        $originalSku = $sku;
        while (self::where('sku', $sku)->exists()) {
            $sku = $originalSku . '-' . $counter;
            $counter++;
        }

        return $sku;
    }
}
