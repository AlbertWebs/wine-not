<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InventoryImport implements ToCollection, WithHeadingRow
{
    public array $summary = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $raw = $row instanceof Collection ? $row->toArray() : (array) $row;
            $rowData = $this->normalizeRowKeys($raw);
            $rowNumber = $index + 2; // Account for heading row

            if ($this->rowIsEmpty($rowData)) {
                $this->summary['skipped']++;
                continue;
            }

            $partNumber = trim((string) ($rowData['part_number'] ?? ''));
            $productName = $this->valueOrNull($rowData['product_name'] ?? $rowData['name'] ?? null);

            if ($partNumber === '' && $productName === '') {
                $this->addError($rowNumber, 'Missing part_number or product name.');
                continue;
            }

            try {
                DB::transaction(function () use ($rowData, $partNumber, $productName) {
                    $inventory = null;
                    if ($partNumber !== '') {
                        $inventory = Inventory::where('part_number', $partNumber)->first();
                    }
                    if (!$inventory && $productName !== '') {
                        $inventory = Inventory::whereRaw('LOWER(TRIM(name)) = ?', [Str::lower(trim($productName))])->first();
                    }
                    $isNew = !$inventory;

                    if ($isNew) {
                        $inventory = new Inventory();
                        $inventory->part_number = $partNumber !== '' ? $partNumber : Inventory::generatePartNumber();
                    }

                    $attributes = $this->buildAttributes($rowData, $inventory, $isNew);

                    $inventory->fill($attributes);

                    if ($isNew && empty($inventory->sku)) {
                        $inventory->sku = Inventory::generateSku(array_merge($attributes, [
                            'part_number' => $inventory->part_number,
                            'category_id' => $attributes['category_id'] ?? $inventory->category_id,
                        ]));
                    }

                    $inventory->save();

                    $this->summary[$isNew ? 'created' : 'updated']++;
                });
            } catch (\Throwable $e) {
                $this->addError($rowNumber, $e->getMessage());
            }
        }
    }

    /**
     * Normalize column keys so "Product name", "Stockist Pricelist", etc. work.
     */
    private function normalizeRowKeys(array $row): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            $normalized = strtolower(preg_replace('/[\s\-]+/', '_', trim((string) $key)));
            if ($normalized !== '') {
                $out[$normalized] = $value;
            }
        }
        return $out;
    }

    private function buildAttributes(array $row, Inventory $inventory, bool $isNew): array
    {
        $attributes = [];

        if (($sku = $this->valueOrNull($row['sku'] ?? null)) !== null) {
            $attributes['sku'] = $sku;
        }

        if (($barcode = $this->valueOrNull($row['barcode'] ?? null)) !== null) {
            $attributes['barcode'] = $barcode;
        }

        $name = $this->valueOrNull($row['name'] ?? $row['product_name'] ?? null);
        if ($isNew && $name === null) {
            throw new \RuntimeException('Name or Product name is required for new inventory items.');
        }
        if ($name !== null) {
            $attributes['name'] = $name;
        }

        if (($description = $this->valueOrNull($row['description'] ?? null)) !== null) {
            $attributes['description'] = $description;
        }

        $volumeMl = $this->parseInteger($row['volume_ml'] ?? null, 'volume_ml');
        if ($volumeMl !== null) {
            $attributes['volume_ml'] = $volumeMl;
        }
        $alcoholPct = $this->parseDecimal($row['alcohol_percentage'] ?? null, 'alcohol_percentage');
        if ($alcoholPct !== null) {
            $attributes['alcohol_percentage'] = $alcoholPct;
        }
        if (($country = $this->valueOrNull($row['country_of_origin'] ?? null)) !== null) {
            $attributes['country_of_origin'] = $country;
        }

        if (($location = $this->valueOrNull($row['location'] ?? null)) !== null) {
            $attributes['location'] = $location;
        }

        $brandName = $this->valueOrNull($row['brand'] ?? $row['distributor'] ?? null);
        if ($brandName !== null) {
            $brand = $this->resolveBrand($brandName);
            $attributes['brand_id'] = $brand->id;
        }

        if (($categoryName = $this->valueOrNull($row['category'] ?? null)) !== null) {
            $category = $this->resolveCategory($categoryName);
            $attributes['category_id'] = $category->id;
        }

        $costPrice = $this->parseDecimal(
            $row['cost_price'] ?? $row['stockist_pricelist'] ?? $row['stockist_price_list'] ?? $this->findColumnValue($row, ['stockist', 'cost', 'price_list']),
            'cost_price'
        );
        if ($costPrice !== null) {
            $attributes['cost_price'] = $costPrice;
        } elseif ($isNew) {
            throw new \RuntimeException('cost_price or Stockist Pricelist is required for new inventory items.');
        }

        $sellingPrice = $this->parseDecimal(
            $row['selling_price'] ?? $row['recommended_resale'] ?? $row['resale'] ?? $row['retail_price'] ?? $row['rrp'] ?? $this->findColumnValue($row, ['recommended_resale', 'resale', 'retail', 'selling', 'rrp']),
            'selling_price'
        );
        if ($sellingPrice !== null) {
            $attributes['selling_price'] = $sellingPrice;
        } elseif ($isNew && $costPrice !== null) {
            $attributes['selling_price'] = $costPrice;
        } elseif ($isNew) {
            throw new \RuntimeException('selling_price or Recommended resale is required for new inventory items.');
        }

        $minPrice = $this->parseDecimal($row['min_price'] ?? null, 'min_price');
        $targetSelling = $sellingPrice ?? $inventory->selling_price;

        if ($minPrice !== null) {
            if ($targetSelling !== null && $minPrice > $targetSelling) {
                throw new \RuntimeException('min_price cannot be greater than selling_price.');
            }
            $attributes['min_price'] = $minPrice;
        } elseif ($isNew) {
            $attributes['min_price'] = $sellingPrice ?? $costPrice ?? 0;
        }

        $stockQuantity = $this->parseInteger(
            $row['stock_quantity'] ?? $row['qty'] ?? $this->findColumnValue($row, ['qty', 'quantity', 'stock']),
            'stock_quantity'
        );
        if ($stockQuantity !== null) {
            $attributes['stock_quantity'] = $stockQuantity;
        } elseif ($isNew) {
            $attributes['stock_quantity'] = 0;
        }

        $reorderLevel = $this->parseInteger($row['reorder_level'] ?? null, 'reorder_level');
        if ($reorderLevel !== null) {
            $attributes['reorder_level'] = $reorderLevel;
        } elseif ($isNew) {
            $attributes['reorder_level'] = 0;
        }

        $status = $this->valueOrNull($row['status'] ?? null);
        if ($status !== null) {
            $normalizedStatus = Str::lower($status);
            if (!in_array($normalizedStatus, ['active', 'inactive'], true)) {
                throw new \RuntimeException('status must be either active or inactive.');
            }
            $attributes['status'] = $normalizedStatus;
        } elseif ($isNew) {
            $attributes['status'] = 'active';
        }

        return $attributes;
    }

    private function valueOrNull($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            return $trimmed === '' ? null : $trimmed;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return null;
    }

    private function parseDecimal($value, string $field): ?float
    {
        $stringValue = $this->valueOrNull($value);

        if ($stringValue === null) {
            return null;
        }

        $normalized = str_replace([',', ' '], ['', ''], $stringValue);

        if (!is_numeric($normalized)) {
            throw new \RuntimeException("{$field} must be a numeric value.");
        }

        return round((float) $normalized, 2);
    }

    private function parseInteger($value, string $field): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }
        if (is_float($value) && !is_nan($value)) {
            return (int) round($value);
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return null;
        }

        $normalized = preg_replace('/[\s,\u{00A0}\u{200B}\u{200C}\u{200D}\u{FEFF}]/u', '', $stringValue);
        $normalized = str_replace(',', '.', $normalized);
        if (is_numeric($normalized)) {
            return (int) round((float) $normalized);
        }
        if (preg_match('/-?\d+\.?\d*/', $normalized, $m)) {
            return (int) round((float) str_replace(',', '.', $m[0]));
        }
        if (preg_match('/\d/', $stringValue)) {
            $asFloat = (float) preg_replace('/[^\d.\-]/', '', $stringValue);
            if (is_finite($asFloat)) {
                return (int) round($asFloat);
            }
        }

        throw new \RuntimeException("{$field} must be an integer value.");
    }

    private function resolveBrand(string $name): Brand
    {
        $existing = Brand::whereRaw('LOWER(brand_name) = ?', [Str::lower($name)])->first();

        return $existing ?: Brand::create(['brand_name' => $name]);
    }

    private function resolveCategory(string $name): Category
    {
        $existing = Category::whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();

        return $existing ?: Category::create(['name' => $name]);
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (!is_null($value) && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Find a cell value by matching column key to any of the given substrings (e.g. "resale", "retail").
     */
    private function findColumnValue(array $row, array $substrings): mixed
    {
        foreach ($row as $key => $value) {
            if ($value === null || trim((string) $value) === '') {
                continue;
            }
            $keyLower = strtolower((string) $key);
            foreach ($substrings as $sub) {
                if (str_contains($keyLower, strtolower($sub))) {
                    return $value;
                }
            }
        }
        return null;
    }

    private function addError(int $rowNumber, string $message): void
    {
        $this->summary['errors'][] = "Row {$rowNumber}: {$message}";
    }
}


