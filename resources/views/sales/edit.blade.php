@extends('layouts.app')

@section('title', 'Edit Sale')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Edit Sale</h1>
            <p class="text-gray-600 mt-1">Invoice {{ $sale->invoice_number }}</p>
        </div>
        <a href="{{ route('sales.show', $sale) }}" class="text-gray-600 hover:text-gray-900">← Back to receipt</a>
    </div>

    @if(session('error'))
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
    @endif

    <form action="{{ route('sales.update', $sale) }}" method="POST" id="editSaleForm" class="bg-white rounded-lg shadow-md p-6">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
            <select name="customer_id" id="customer_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Walk-in</option>
                @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ old('customer_id', $sale->customer_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
                <label class="block text-sm font-medium text-gray-700">Items</label>
                <button type="button" id="addRow" class="text-sm text-blue-600 hover:text-blue-800 font-medium">+ Add product</button>
            </div>
            <div class="border rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-24">Qty</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-32">Price (KES)</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-28">Subtotal</th>
                            <th class="w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        @foreach(old('items', $editItems) as $idx => $item)
                        @php
                            $part = $item['part'] ?? \App\Models\Inventory::find($item['part_id'] ?? 0);
                            $qty = (int)($item['quantity'] ?? 1);
                            $price = (float)($item['price'] ?? 0);
                        @endphp
                        @if($part)
                        <tr class="item-row border-t border-gray-200">
                            <td class="px-4 py-2">
                                <input type="hidden" name="items[{{ $idx }}][part_id]" value="{{ $part->id }}">
                                <span class="text-sm font-medium text-gray-900">{{ $part->name }}</span>
                                <span class="text-xs text-gray-500 block">{{ $part->part_number }}</span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <input type="number" name="items[{{ $idx }}][quantity]" value="{{ $qty }}" min="1" class="item-qty w-20 text-right px-2 py-1 border border-gray-300 rounded" required>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <input type="number" name="items[{{ $idx }}][price]" value="{{ $price }}" min="0" step="0.01" class="item-price w-28 text-right px-2 py-1 border border-gray-300 rounded" required>
                            </td>
                            <td class="px-4 py-2 text-right item-subtotal text-sm font-medium">KES {{ number_format($qty * $price, 2) }}</td>
                            <td class="px-2 py-2">
                                <button type="button" class="remove-row text-red-600 hover:text-red-800 p-1" title="Remove">×</button>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500 mt-1">Ensure at least one item. Min price rules apply on save.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label for="tax" class="block text-sm font-medium text-gray-700 mb-1">Tax (KES)</label>
                <input type="number" name="tax" id="tax" value="{{ old('tax', $sale->tax) }}" min="0" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label for="discount" class="block text-sm font-medium text-gray-700 mb-1">Discount (KES)</label>
                <input type="number" name="discount" id="discount" value="{{ old('discount', $sale->discount) }}" min="0" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex items-end">
                <div class="w-full">
                    <span class="block text-sm font-medium text-gray-700">Total</span>
                    <p id="totalDisplay" class="text-xl font-bold text-gray-900">KES {{ number_format($sale->total_amount, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('sales.show', $sale) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Update Sale</button>
        </div>
    </form>
</div>

<script>
(function() {
    const inventory = @json($inventoryJson);
    let rowIndex = document.querySelectorAll('#itemsBody .item-row').length;

    function updateRowSubtotal(row) {
        const qty = parseInt(row.querySelector('.item-qty').value, 10) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const sub = row.querySelector('.item-subtotal');
        if (sub) sub.textContent = 'KES ' + (qty * price).toFixed(2);
        updateTotal();
    }

    function updateTotal() {
        let subtotal = 0;
        document.querySelectorAll('#itemsBody .item-row').forEach(row => {
            const qty = parseInt(row.querySelector('.item-qty').value, 10) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            subtotal += qty * price;
        });
        const tax = parseFloat(document.getElementById('tax').value) || 0;
        const discount = parseFloat(document.getElementById('discount').value) || 0;
        const total = Math.round((subtotal + tax - discount) * 100) / 100;
        document.getElementById('totalDisplay').textContent = 'KES ' + total.toLocaleString('en-KE', { minimumFractionDigits: 2 });
    }

    document.getElementById('itemsBody').addEventListener('input', function(e) {
        if (e.target.matches('.item-qty, .item-price')) {
            updateRowSubtotal(e.target.closest('.item-row'));
        }
    });
    document.getElementById('itemsBody').addEventListener('change', function(e) {
        if (e.target.matches('.item-qty, .item-price')) updateRowSubtotal(e.target.closest('.item-row'));
    });
    document.getElementById('tax').addEventListener('input', updateTotal);
    document.getElementById('discount').addEventListener('input', updateTotal);

    document.getElementById('itemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            const row = e.target.closest('.item-row');
            if (document.querySelectorAll('#itemsBody .item-row').length > 1) row.remove();
            reindexRows();
            updateTotal();
        }
    });

    function reindexRows() {
        document.querySelectorAll('#itemsBody .item-row').forEach((row, i) => {
            row.querySelectorAll('input[name^="items["]').forEach(input => {
                input.name = input.name.replace(/items\[\d+\]/, 'items[' + i + ']');
            });
        });
    }

    document.getElementById('addRow').addEventListener('click', function() {
        const idx = document.querySelectorAll('#itemsBody .item-row').length;
        const select = document.createElement('select');
        select.className = 'product-select w-full px-2 py-1 border border-gray-300 rounded';
        select.innerHTML = '<option value="">Select product...</option>' + inventory.map(p => 
            '<option value="' + p.id + '" data-price="' + p.selling_price + '" data-name="' + (p.name || '').replace(/"/g, '&quot;') + '">' + (p.name || '') + ' (' + (p.part_number || '') + ') - KES ' + p.selling_price + '</option>'
        ).join('');
        const tr = document.createElement('tr');
        tr.className = 'item-row border-t border-gray-200';
        const td1 = document.createElement('td');
        td1.className = 'px-4 py-2';
        td1.appendChild(select);
        const td2 = document.createElement('td');
        td2.className = 'px-4 py-2 text-right';
        const qtyInput = document.createElement('input');
        qtyInput.type = 'number';
        qtyInput.className = 'item-qty w-20 text-right px-2 py-1 border border-gray-300 rounded';
        qtyInput.value = '1';
        qtyInput.min = 1;
        qtyInput.name = 'items[' + idx + '][quantity]';
        qtyInput.required = true;
        td2.appendChild(qtyInput);
        const td3 = document.createElement('td');
        td3.className = 'px-4 py-2 text-right';
        const priceInput = document.createElement('input');
        priceInput.type = 'number';
        priceInput.className = 'item-price w-28 text-right px-2 py-1 border border-gray-300 rounded';
        priceInput.value = '0';
        priceInput.min = 0;
        priceInput.step = '0.01';
        priceInput.name = 'items[' + idx + '][price]';
        priceInput.required = true;
        td3.appendChild(priceInput);
        const td4 = document.createElement('td');
        td4.className = 'px-4 py-2 text-right item-subtotal text-sm font-medium';
        td4.textContent = 'KES 0.00';
        const td5 = document.createElement('td');
        td5.className = 'px-2 py-2';
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-row text-red-600 hover:text-red-800 p-1';
        removeBtn.textContent = '×';
        td5.appendChild(removeBtn);
        tr.append(td1, td2, td3, td4, td5);
        document.getElementById('itemsBody').appendChild(tr);
        select.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt.value) {
                const price = parseFloat(opt.dataset.price) || 0;
                priceInput.value = price;
                const m = qtyInput.name.match(/items\[(\d+)\]/);
                const rowIdx = m ? m[1] : idx;
                const hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'items[' + rowIdx + '][part_id]';
                hid.value = opt.value;
                td1.innerHTML = '';
                td1.appendChild(hid);
                const nameSpan = document.createElement('span');
                nameSpan.className = 'text-sm font-medium text-gray-900';
                nameSpan.textContent = opt.dataset.name || opt.textContent.split(' - ')[0];
                td1.appendChild(nameSpan);
                const partNum = document.createElement('span');
                partNum.className = 'text-xs text-gray-500 block';
                partNum.textContent = opt.textContent.match(/\(([^)]+)\)/)?.[1] || '';
                td1.appendChild(partNum);
                updateRowSubtotal(tr);
            }
        });
        reindexRows();
    });

    // Initial total from tax/discount
    updateTotal();
})();
</script>
@endsection
