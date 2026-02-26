@extends('layouts.app')

@section('title', 'Add Inventory Item')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('inventory.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Inventory
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Add Product</h1>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8">
        <form method="POST" action="{{ route('inventory.store') }}" x-data='inventoryForm({
            inventoryId: null,
            initialPartNumber: "",
            initialSku: @json(old("sku", "")),
            initialBarcode: @json(old("barcode", "")),
            checkUrl: @json(route("inventory.checkUnique")),
            editUrlTemplate: @json(route("inventory.edit", "__ID__")),
            redirectOnDuplicate: true
        })'>
            @csrf

            <!-- Basic Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- SKU / Product Code (auto-generated) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SKU / Product Code <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-600">
                            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-sm">Auto-generated when you save (e.g. WN-000001)</span>
                        </div>
                    </div>

                    <!-- Barcode -->
                    <div>
                        <label for="barcode" class="block text-sm font-medium text-gray-700 mb-2">
                            Barcode
                            <span class="text-xs text-gray-500 font-normal">(Scan or enter manually)</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                name="barcode" 
                                id="barcode"
                                x-model="barcode"
                                @input.debounce.400ms="checkBarcode()"
                                @blur="checkBarcode(true)"
                                value="{{ old('barcode') }}"
                                x-ref="barcodeInput"
                                @keydown="handleBarcodeInput($event)"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('barcode') border-red-500 @enderror"
                                placeholder="Scan barcode or enter manually"
                                autocomplete="off"
                            >
                            <button 
                                type="button"
                                @click="toggleCameraScanner"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-gray-500 hover:text-blue-600 transition"
                                title="Toggle camera scanner"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                        @error('barcode')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div class="mt-1 text-sm" x-cloak>
                            <p x-show="checkingBarcode" class="text-blue-600 flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                Checking availability...
                            </p>
                            <p x-show="!checkingBarcode && barcodeState === 'available'" class="text-green-600 flex items-center gap-2">
                                <span>✓</span>
                                <span x-text="barcodeMessage"></span>
                            </p>
                            <p x-show="!checkingBarcode && barcodeState === 'exists'" class="text-red-600 flex items-center gap-2">
                                <span>!</span>
                                <span x-text="barcodeMessage"></span>
                            </p>
                            <p x-show="!checkingBarcode && barcodeState === 'current'" class="text-gray-600 flex items-center gap-2">
                                <span>•</span>
                                <span x-text="barcodeMessage"></span>
                            </p>
                            <p x-show="!checkingBarcode && barcodeState === 'error'" class="text-amber-600 flex items-center gap-2">
                                <span>⚠</span>
                                <span x-text="barcodeMessage"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Product Name -->
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Product Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name"
                            value="{{ old('name') }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                            placeholder="e.g., Cabernet Sauvignon"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea 
                            name="description" 
                            id="description"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                            placeholder="Item description..."
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Categorization -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Categorization</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Category -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select 
                            name="category_id" 
                            id="category_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('category_id') border-red-500 @enderror"
                        >
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Brand -->
                    <div>
                        <label for="brand_id" class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                        <select 
                            name="brand_id" 
                            id="brand_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('brand_id') border-red-500 @enderror"
                        >
                            <option value="">Select Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->brand_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('brand_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Volume (ml) -->
                    <div>
                        <label for="volume_ml" class="block text-sm font-medium text-gray-700 mb-2">Volume (ml)</label>
                        <input 
                            type="number" 
                            name="volume_ml" 
                            id="volume_ml"
                            min="0"
                            value="{{ old('volume_ml') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('volume_ml') border-red-500 @enderror"
                            placeholder="e.g., 750"
                        >
                        @error('volume_ml')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Alcohol % -->
                    <div>
                        <label for="alcohol_percentage" class="block text-sm font-medium text-gray-700 mb-2">Alcohol % (ABV)</label>
                        <input 
                            type="number" 
                            step="0.1"
                            min="0"
                            max="100"
                            name="alcohol_percentage" 
                            id="alcohol_percentage"
                            value="{{ old('alcohol_percentage') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('alcohol_percentage') border-red-500 @enderror"
                            placeholder="e.g., 13.5"
                        >
                        @error('alcohol_percentage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Country of Origin -->
                    <div>
                        <label for="country_of_origin" class="block text-sm font-medium text-gray-700 mb-2">Country of Origin</label>
                        <input 
                            type="text" 
                            name="country_of_origin" 
                            id="country_of_origin"
                            value="{{ old('country_of_origin') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('country_of_origin') border-red-500 @enderror"
                            placeholder="e.g., France"
                        >
                        @error('country_of_origin')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Pricing & Stock -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Pricing & Stock</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Cost Price -->
                    <div>
                        <label for="cost_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Cost Price (KES) <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            step="0.01"
                            min="0"
                            name="cost_price" 
                            id="cost_price"
                            value="{{ old('cost_price') }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('cost_price') border-red-500 @enderror"
                            placeholder="0.00"
                        >
                        @error('cost_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Min Price -->
                    <div>
                        <label for="min_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Min Price (KES) <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            step="0.01"
                            min="0"
                            name="min_price" 
                            id="min_price"
                            x-model="minPrice"
                            value="{{ old('min_price') }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('min_price') border-red-500 @enderror"
                            placeholder="0.00"
                        >
                        @error('min_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Cannot sell below this price</p>
                    </div>

                    <!-- Selling Price -->
                    <div>
                        <label for="selling_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Selling Price (KES) <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            step="0.01"
                            min="0"
                            name="selling_price" 
                            id="selling_price"
                            x-model="sellingPrice"
                            value="{{ old('selling_price') }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('selling_price') border-red-500 @enderror"
                            placeholder="0.00"
                        >
                        @error('selling_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Stock Quantity -->
                    <div>
                        <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-2">
                            Stock Quantity <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            min="0"
                            name="stock_quantity" 
                            id="stock_quantity"
                            value="{{ old('stock_quantity', 0) }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('stock_quantity') border-red-500 @enderror"
                            placeholder="0"
                        >
                        @error('stock_quantity')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reorder Level -->
                    <div>
                        <label for="reorder_level" class="block text-sm font-medium text-gray-700 mb-2">
                            Reorder Level <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            min="0"
                            name="reorder_level" 
                            id="reorder_level"
                            value="{{ old('reorder_level', 0) }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('reorder_level') border-red-500 @enderror"
                            placeholder="0"
                        >
                        @error('reorder_level')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Alert when stock reaches this level</p>
                    </div>

                    <!-- Location -->
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-2">Location (Shelf/Bin)</label>
                        <input 
                            type="text" 
                            name="location" 
                            id="location"
                            value="{{ old('location', 'Shelf') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('location') border-red-500 @enderror"
                            placeholder="e.g., Aisle 3, Shelf B"
                        >
                        @error('location')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="status" 
                            id="status"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 @enderror"
                        >
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t">
                <a href="{{ route('inventory.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition">
                    Add Product
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    @once
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endonce
    <script>
    function inventoryForm(config = {}) {
        const settings = Object.assign({
            inventoryId: null,
            initialPartNumber: '',
            initialBarcode: '',
            checkUrl: '',
            editUrlTemplate: null,
            redirectOnDuplicate: false
        }, config);

        return {
            minPrice: {{ old('min_price', 0) }},
            sellingPrice: {{ old('selling_price', 0) }},
            barcodeInputTimeout: null,
            lastBarcodeInputTime: 0,
            cameraScannerActive: false,
            inventoryId: settings.inventoryId,
            checkUrl: settings.checkUrl,
            editUrlTemplate: settings.editUrlTemplate,
            redirectOnDuplicate: settings.redirectOnDuplicate,
            partNumber: settings.initialPartNumber ?? '',
            originalPartNumber: (settings.initialPartNumber ?? '').trim(),
            partNumberState: null,
            partNumberMessage: '',
            checkingPartNumber: false,
            lastCheckedPartNumber: '',
            barcode: settings.initialBarcode ?? '',
            originalBarcode: (settings.initialBarcode ?? '').trim(),
            barcodeState: null,
            barcodeMessage: '',
            checkingBarcode: false,
            lastCheckedBarcode: '',
            
            init() {
                this.$nextTick(() => {
                    this.$refs.barcodeInput?.focus();
                });

                document.addEventListener('keydown', (e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                        e.preventDefault();
                        this.$refs.barcodeInput?.focus();
                    }
                });

                if (this.partNumber) {
                    this.checkPartNumber(true);
                }

                if (this.barcode) {
                    this.checkBarcode(true);
                }
            },

            async checkPartNumber(force = false) {
                const value = (this.partNumber || '').trim();

                if (!value) {
                    this.partNumberState = null;
                    this.partNumberMessage = '';
                    this.lastCheckedPartNumber = '';
                    return;
                }

                if (this.inventoryId && value === this.originalPartNumber) {
                    this.partNumberState = 'current';
                    this.partNumberMessage = 'Part number remains assigned to this inventory item.';
                    this.lastCheckedPartNumber = value;
                    return;
                }

                if (!force && value === this.lastCheckedPartNumber && this.partNumberState !== 'error') {
                    return;
                }

                this.checkingPartNumber = true;

                try {
                    const result = await this.checkValue('part_number', value);
                    if (result.exists && this.redirectOnDuplicate && result.item && this.editUrlTemplate) {
                        const proceed = await this.promptRedirect(result.item, 'Part number');
                        if (proceed) {
                            window.location.href = this.editUrlTemplate.replace('__ID__', result.item.id);
                            return;
                        }
                    }

                    this.partNumberState = result.exists ? 'exists' : 'available';
                    this.partNumberMessage = result.message || (result.exists
                        ? 'Part number is already in use.'
                        : 'Part number is available and not yet used.');
                } catch (error) {
                    console.error(error);
                    this.partNumberState = 'error';
                    this.partNumberMessage = 'Unable to verify part number right now.';
                } finally {
                    this.checkingPartNumber = false;
                    this.lastCheckedPartNumber = value;
                }
            },

            async checkBarcode(force = false) {
                const value = (this.barcode || '').trim();

                if (!value) {
                    this.barcodeState = null;
                    this.barcodeMessage = '';
                    this.lastCheckedBarcode = '';
                    return;
                }

                if (this.inventoryId && value === this.originalBarcode) {
                    this.barcodeState = 'current';
                    this.barcodeMessage = 'Barcode remains assigned to this inventory item.';
                    this.lastCheckedBarcode = value;
                    return;
                }

                if (!force && value === this.lastCheckedBarcode && this.barcodeState !== 'error') {
                    return;
                }

                this.checkingBarcode = true;

                try {
                    const result = await this.checkValue('barcode', value);
                    this.barcodeState = result.exists ? 'exists' : 'available';
                    this.barcodeMessage = result.message || (result.exists
                        ? 'Barcode is already in use.'
                        : 'Barcode is available and not yet used.');
                } catch (error) {
                    console.error(error);
                    this.barcodeState = 'error';
                    this.barcodeMessage = 'Unable to verify barcode right now.';
                } finally {
                    this.checkingBarcode = false;
                    this.lastCheckedBarcode = value;
                }
            },

            async checkValue(field, value) {
                if (!this.checkUrl) {
                    throw new Error('Unique check URL is not configured.');
                }

                const params = new URLSearchParams({ field, value });

                if (this.inventoryId) {
                    params.append('ignore_id', this.inventoryId);
                }

                const response = await fetch(`${this.checkUrl}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok.');
                }

                return await response.json();
            },

            async promptRedirect(item, fieldLabel) {
                const itemName = item?.name || 'another inventory item';
                const title = `${fieldLabel} already exists`;
                const text = `${fieldLabel} is currently linked to ${itemName}. Do you want to open it for editing?`;

                if (typeof Swal === 'undefined') {
                    return confirm(`${title}\n\n${text}`);
                }

                const result = await Swal.fire({
                    title,
                    text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Edit Item',
                    cancelButtonText: 'Stay Here',
                    reverseButtons: true,
                    focusCancel: true,
                });

                return result.isConfirmed;
            },

            handleBarcodeInput(event) {
                const currentTime = Date.now();

                if (event.key === 'Enter' || event.key === 'Tab') {
                    if (this.barcodeInputTimeout) {
                        clearTimeout(this.barcodeInputTimeout);
                        this.barcodeInputTimeout = null;
                    }

                    if (event.key === 'Enter' && (currentTime - this.lastBarcodeInputTime) < 100) {
                        event.preventDefault();
                        return false;
                    }
                } else {
                    this.lastBarcodeInputTime = currentTime;

                    if (this.barcodeInputTimeout) {
                        clearTimeout(this.barcodeInputTimeout);
                    }

                    this.barcodeInputTimeout = setTimeout(() => {
                        // End of barcode scan - hook for additional logic if needed
                    }, 200);
                }
            },

            toggleCameraScanner() {
                alert('Camera scanner functionality can be added here. Would you like to integrate a barcode scanning library?');
            }
        };
    }
    </script>
@endpush
@endsection

