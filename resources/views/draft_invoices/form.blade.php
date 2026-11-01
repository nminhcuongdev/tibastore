<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $invoice->exists ? 'Hóa đơn nháp #'.$invoice->id : 'Tạo hóa đơn nháp' }} — Tiba Store</title>@include('partials.favicon') @include('draft_invoices.styles')</head>
<body><div class="layout">@include('partials.sidebar', ['active' => 'draft-invoices'])<main class="page">
<form id="invoice-form" method="POST" action="{{ $invoice->exists ? route('draft-invoices.update', $invoice) : route('draft-invoices.store') }}">
@csrf @if($invoice->exists) @method('PUT') @endif
<div class="toolbar no-print"><h1>{{ $invoice->exists ? 'Hóa đơn nháp #'.$invoice->id : 'Tạo hóa đơn nháp' }}</h1><a class="button" href="{{ route('draft-invoices.index') }}">Danh sách</a><button type="button" id="print-invoice">In / Lưu PDF</button><button class="primary" type="submit">Lưu nháp</button></div>
<p class="hint no-print" id="save-state" role="status">Điền trực tiếp vào các ô trong phiếu. Bấm Lưu nháp để lưu lại và mở trên máy khác.</p>
@if(session('status'))<div class="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="errors" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="editor-wrap"><section class="paper">
<h2>TIBA STORE - TRANG PHỤC BIỂU DIỄN</h2><h3>PHIẾU BÁN HÀNG / HÓA ĐƠN</h3>
<div class="invoice-date">Ngày <input type="date" name="invoice_date" aria-label="Ngày hóa đơn" required value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d')) }}"></div>
<table class="customer"><tbody><tr><th style="width:24%;text-align:left">Khách hàng:</th><td style="width:36%"><textarea name="customer_name" rows="1" maxlength="255" required aria-label="Khách hàng">{{ old('customer_name', $invoice->customer_name) }}</textarea></td><td><strong>SĐT:</strong><input name="phone" maxlength="50" aria-label="Số điện thoại" value="{{ old('phone', $invoice->phone) }}"></td></tr><tr><td>Địa chỉ</td><td><textarea name="address" rows="3" maxlength="2000" aria-label="Địa chỉ">{{ old('address', $invoice->address) }}</textarea></td><td><strong>Mã số thuế:</strong><input name="tax_code" maxlength="100" aria-label="Mã số thuế" value="{{ old('tax_code', $invoice->tax_code) }}"></td></tr></tbody></table>
<table class="items"><colgroup><col style="width:5%"><col style="width:27%"><col style="width:8%"><col style="width:8%"><col style="width:16%"><col style="width:18%"><col style="width:18%"><col class="no-print" style="width:34px"></colgroup><thead><tr><th>STT</th><th>Tên sản phẩm</th><th>ĐVT</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th><th>Ghi chú</th><th class="no-print"></th></tr></thead><tbody id="invoice-items"></tbody><tfoot><tr class="total"><td colspan="5">TỔNG THANH TOÁN</td><td colspan="2" class="money" id="invoice-total">0</td><td class="no-print"></td></tr></tfoot></table>
<div class="row-actions no-print"><button type="button" id="add-row">+ Thêm dòng sản phẩm</button></div>
</section></div></form>
<template id="invoice-row"><tr><td data-number></td><td><textarea rows="1" data-field="name" required maxlength="255" aria-label="Tên sản phẩm"></textarea></td><td><input data-field="unit" maxlength="30" aria-label="Đơn vị tính"></td><td><input type="number" data-field="quantity" min="0.01" max="1000000" step="0.01" required aria-label="Số lượng"></td><td><input type="number" data-field="unit_price" min="0" max="1000000000" step="1" required aria-label="Đơn giá (đồng)"></td><td class="money" data-total></td><td><textarea rows="1" data-field="note" maxlength="1000" aria-label="Ghi chú"></textarea></td><td class="no-print"><button type="button" class="delete-row" title="Xóa dòng" aria-label="Xóa dòng">×</button></td></tr></template>
@php($initialItems = old('items', $invoice->items ?? [['name' => '', 'unit' => 'Bộ', 'quantity' => 1, 'unit_price' => 0, 'note' => '']]))
<script>
(function () {
    const initial = @json($initialItems);
    const body = document.getElementById('invoice-items');
    const form = document.getElementById('invoice-form');
    const state = document.getElementById('save-state');
    const money = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 });
    let dirty = false;
    function resize(input) { if (input.tagName === 'TEXTAREA') { input.style.height = 'auto'; input.style.height = input.scrollHeight + 'px'; } }
    function recalculate() {
        let total = 0;
        Array.from(body.rows).forEach(function (row, i) {
            row.querySelector('[data-number]').textContent = i + 1;
            row.querySelectorAll('[data-field]').forEach(function (input) { input.name = 'items[' + i + '][' + input.dataset.field + ']'; });
            const amount = Math.round(Number(row.querySelector('[data-field="quantity"]').value || 0) * Number(row.querySelector('[data-field="unit_price"]').value || 0));
            row.querySelector('[data-total]').textContent = money.format(amount);
            total += amount;
        });
        document.getElementById('invoice-total').textContent = money.format(total);
        document.getElementById('add-row').disabled = body.rows.length >= 100;
        body.querySelectorAll('.delete-row').forEach(b => b.disabled = body.rows.length <= 1);
    }
    function add(item) {
        const row = document.getElementById('invoice-row').content.cloneNode(true);
        row.querySelectorAll('[data-field]').forEach(input => input.value = item[input.dataset.field] ?? '');
        body.appendChild(row);
        recalculate();
        body.lastElementChild.querySelectorAll('textarea').forEach(resize);
    }
    function changed() { dirty = true; state.textContent = 'Có thay đổi chưa lưu. Bấm Lưu nháp để lưu hóa đơn.'; }
    initial.forEach(add);
    document.getElementById('add-row').addEventListener('click', function () {
        if (body.rows.length >= 100) return;
        add({name:'', unit:'Bộ', quantity:1, unit_price:0, note:''}); changed();
        body.lastElementChild.querySelector('textarea').focus();
    });
    body.addEventListener('click', function (event) { const button = event.target.closest('.delete-row'); if (button && body.rows.length > 1) { button.closest('tr').remove(); recalculate(); changed(); } });
    form.addEventListener('input', function (event) { resize(event.target); recalculate(); changed(); });
    function preparePrint() {
        form.querySelectorAll('input:not([type="hidden"]),textarea').forEach(function (input) {
            let span = input.nextElementSibling;
            if (!span || !span.classList.contains('print-value')) { span = document.createElement('span'); span.className = 'print-value'; input.after(span); }
            let value = input.value;
            if (input.type === 'date' && value) value = value.split('-').reverse().join('/');
            if (input.dataset.field === 'unit_price') value = money.format(Number(value));
            span.textContent = value || '\u00a0';
        });
    }
    window.addEventListener('beforeprint', preparePrint);
    document.getElementById('print-invoice').addEventListener('click', function () { if (!form.reportValidity()) return; preparePrint(); window.print(); });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (event) { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
    form.querySelectorAll('textarea').forEach(resize);
})();
</script></main></div></body></html>
