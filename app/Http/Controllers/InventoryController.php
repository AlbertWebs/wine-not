<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Category;
use App\Models\Brand;
use App\Imports\InventoryImport;
use App\Exports\InventoryTemplateExport;
use App\Exports\InventorySimpleTemplateExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Inventory::with(['category', 'brand']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('part_number', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('country_of_origin', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('low_stock')) {
            $query->lowStock();
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $inventory = $query->paginate(15);
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('brand_name')->get();
        $lowStockCount = Inventory::active()->lowStock()->count();

        return view('inventory.index', compact('inventory', 'categories', 'brands', 'lowStockCount'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('brand_name')->get();

        return view('inventory.create', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'part_number' => 'nullable|string|unique:inventory,part_number|max:255',
            'barcode' => 'nullable|string|unique:inventory,barcode|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',
            'volume_ml' => 'nullable|integer|min:0',
            'alcohol_percentage' => 'nullable|numeric|min:0|max:100',
            'country_of_origin' => 'nullable|string|max:255',
            'cost_price' => 'required|numeric|min:0',
            'min_price' => 'required|numeric|min:0|lte:selling_price',
            'selling_price' => 'required|numeric|min:0|gte:min_price',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        if (empty($validated['part_number'])) {
            $validated['part_number'] = Inventory::generatePartNumber();
        }
        if (empty($validated['sku'])) {
            $validated['sku'] = Inventory::generateSku($validated);
        }

        if (empty($validated['location'])) {
            $validated['location'] = 'Shelf';
        }

        Inventory::create($validated);

        return redirect()->route('inventory.index')
            ->with('success', 'Product added successfully.');
    }

    public function show(Inventory $inventory)
    {
        $inventory->load(['category', 'brand', 'priceHistories.changedBy']);

        return view('inventory.show', compact('inventory'));
    }

    public function edit(Inventory $inventory)
    {
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('brand_name')->get();

        return view('inventory.edit', compact('inventory', 'categories', 'brands'));
    }

    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'part_number' => 'required|string|max:255|unique:inventory,part_number,' . $inventory->id,
            'barcode' => 'nullable|string|max:255|unique:inventory,barcode,' . $inventory->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',
            'volume_ml' => 'nullable|integer|min:0',
            'alcohol_percentage' => 'nullable|numeric|min:0|max:100',
            'country_of_origin' => 'nullable|string|max:255',
            'cost_price' => 'required|numeric|min:0',
            'min_price' => 'required|numeric|min:0|lte:selling_price',
            'selling_price' => 'required|numeric|min:0|gte:min_price',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        if (empty($validated['sku']) || !isset($validated['sku'])) {
            $validated['sku'] = Inventory::generateSku($validated);
        }

        if (array_key_exists('location', $validated) && empty($validated['location'])) {
            $validated['location'] = 'Shelf';
        }

        if ($request->selling_price != $inventory->selling_price) {
            \App\Models\PriceHistory::create([
                'part_id' => $inventory->id,
                'old_price' => $inventory->selling_price,
                'new_price' => $request->selling_price,
                'changed_by' => Auth::id(),
                'changed_at' => now(),
            ]);
        }

        $inventory->update($validated);

        return redirect()->route('inventory.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Inventory $inventory)
    {
        if ($inventory->saleItems()->count() > 0) {
            return redirect()->route('inventory.index')
                ->with('error', 'Cannot delete a product that has been sold. Consider marking it as inactive instead.');
        }

        $inventory->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|exists:inventory,id',
        ]);

        DB::beginTransaction();
        try {
            $deleted = 0;
            $failed = 0;

            foreach ($validated['ids'] as $id) {
                $inventory = Inventory::find($id);
                if ($inventory && $inventory->saleItems()->count() > 0) {
                    $failed++;
                    continue;
                }
                if ($inventory) {
                    $inventory->delete();
                    $deleted++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deleted} item(s)" . ($failed > 0 ? ". {$failed} item(s) could not be deleted (used in sales)." : ''),
                'deleted' => $deleted,
                'failed' => $failed,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete items: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function showImportForm()
    {
        return view('inventory.import', [
            'summary' => session('import_summary'),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new InventoryImport();
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->withInput()
                ->withErrors(['file' => 'Failed to process the uploaded file. Please ensure it is a valid Excel document.']);
        }

        $summary = $import->summary;
        $messageParts = [
            $summary['created'] . ' created',
            $summary['updated'] . ' updated',
        ];
        if ($summary['skipped'] > 0) {
            $messageParts[] = $summary['skipped'] . ' skipped';
        }
        if (count($summary['errors']) > 0) {
            $messageParts[] = count($summary['errors']) . ' issue(s) to review';
        }

        return redirect()
            ->route('inventory.import.form')
            ->with('success', 'Import complete: ' . implode(', ', $messageParts) . '.')
            ->with('import_summary', $summary);
    }

    public function downloadTemplate()
    {
        $fileName = 'inventory-import-template-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new InventoryTemplateExport(), $fileName);
    }

    public function downloadSimpleTemplate()
    {
        $fileName = 'inventory-simple-template-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new InventorySimpleTemplateExport(), $fileName);
    }

    public function checkUnique(Request $request)
    {
        $validated = $request->validate([
            'field' => ['required', Rule::in(['part_number', 'barcode'])],
            'value' => ['nullable', 'string', 'max:255'],
            'ignore_id' => ['nullable', 'integer'],
        ]);

        $value = trim($validated['value'] ?? '');
        $label = $validated['field'] === 'part_number' ? 'SKU / Part number' : 'Barcode';

        if ($value === '') {
            return response()->json([
                'exists' => false,
                'message' => "Enter a {$label} to check.",
            ]);
        }

        $query = Inventory::query()->where($validated['field'], $value);
        if (!empty($validated['ignore_id'])) {
            $query->where('id', '!=', $validated['ignore_id']);
        }
        $existing = $query->first(['id', 'name', 'part_number', 'barcode']);

        return response()->json([
            'exists' => (bool) $existing,
            'message' => $existing
                ? sprintf('%s already belongs to %s (ID #%d).', $label, $existing->name ?? 'another product', $existing->id)
                : sprintf('%s is available.', $label),
            'item' => $existing ? [
                'id' => $existing->id,
                'name' => $existing->name,
                'part_number' => $existing->part_number,
                'barcode' => $existing->barcode,
            ] : null,
        ]);
    }
}
