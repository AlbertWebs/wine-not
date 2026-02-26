@extends('layouts.app')

@section('title', 'Import Inventory')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Import Inventory</h1>
            <p class="text-gray-600 mt-1">Upload an Excel sheet to create or update inventory records in bulk.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('inventory.template') }}" class="px-4 py-2 border border-gray-300 hover:bg-gray-50 rounded-lg font-semibold transition flex items-center gap-2 text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-7 4V4m0 0L9 7m3-3l3 3" />
                </svg>
                Full Template
            </a>
            <a href="{{ route('inventory.template.simple') }}" class="px-4 py-2 border border-blue-600 text-blue-600 hover:bg-blue-50 rounded-lg font-semibold transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-7 4V4m0 0L9 7m3-3l3 3" />
                </svg>
                Simple Template (Product, QTY, Prices, Distributor)
            </a>
            <a href="{{ route('inventory.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-semibold transition">
                Back to Inventory
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">How it works</h2>
        <p class="text-sm text-gray-700">Two formats are supported. Use the one that matches your sheet.</p>
        <div class="grid md:grid-cols-2 gap-4">
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 mb-2">Simple format (recommended)</h3>
                <p class="text-sm text-gray-600 mb-2">Columns: <strong>Product name</strong>, <strong>QTY</strong>, <strong>Stockist Pricelist</strong>, <strong>Recommended resale</strong>, <strong>Distributor</strong>.</p>
                <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
                    <li>Product name → inventory name</li>
                    <li>QTY → stock quantity</li>
                    <li>Stockist Pricelist → cost price</li>
                    <li>Recommended resale → selling price</li>
                    <li>Distributor → brand (created if missing)</li>
                </ul>
                <p class="text-xs text-gray-500 mt-2">SKU is auto-generated. Matching by product name when updating.</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 mb-2">Full format</h3>
                <p class="text-sm text-gray-600 mb-2">Use the <strong>Full Template</strong> for part_number, sku, barcode, category, volume_ml, etc.</p>
                <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
                    <li>Rows matched by <span class="font-semibold">part_number</span> or product name</li>
                    <li>Numeric fields: numbers only. Status: <code>active</code> or <code>inactive</code></li>
                </ul>
            </div>
        </div>
        <p class="text-sm text-gray-600">Save your sheet as <code>.xlsx</code>, <code>.xls</code>, or <code>.csv</code> before uploading.</p>
    </div>

    @if(!empty($summary))
    <div class="bg-white rounded-lg shadow-md p-6 space-y-4 border border-blue-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Latest import summary</h3>
                <p class="text-sm text-gray-600">Created {{ $summary['created'] }} · Updated {{ $summary['updated'] }} · Skipped {{ $summary['skipped'] }}</p>
            </div>
        </div>
        @if(!empty($summary['errors']))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 space-y-2">
            <h4 class="text-sm font-semibold text-red-700">Issues to review ({{ count($summary['errors']) }})</h4>
            <ul class="text-sm text-red-600 space-y-1 list-disc pl-5">
                @foreach($summary['errors'] as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="{{ route('inventory.import') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700 mb-2">Upload Excel file</label>
                <input
                    type="file"
                    name="file"
                    id="file"
                    accept=".xlsx,.xls,.csv"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700"
                >
                <p class="mt-2 text-xs text-gray-500">Maximum file size: 5MB. Supported formats: XLSX, XLS, CSV.</p>
                @error('file')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition">
                    Upload and Process
                </button>
            </div>
        </form>
    </div>
</div>
@endsection



