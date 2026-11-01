<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $invoice->exists ? 'Hóa đơn nháp #'.$invoice->id : 'Tạo hóa đơn nháp' }} — Tiba Store</title>@include('partials.favicon') @include('draft_invoices.styles')</head>
<body><div class="layout">@include('partials.sidebar', ['active' => 'draft-invoices'])<main class="page">
<form id="invoice-form" method="POST" action="{{ $invoice->exists ? route('draft-invoices.update', $invoice) : route('draft-invoices.store') }}">
@csrf @if($invoice->exists) @method('PUT') @endif
<div class="toolbar no-print"><h1>{{ $invoice->exists ? 'Hóa đơn nháp #'.$invoice->id : 'Tạo hóa đơn nháp' }}</h1><a class="button" href="{{ route('draft-invoices.index') }}">Danh sách</a><button type="button" id="print-invoice">In / Lưu PDF</button><button class="primary" type="submit">Lưu nháp</button></div>
<p class="hint no-print" id="save-state" role="status">Điền trực tiếp vào các ô trong phiếu. Bấm Lưu nháp để lưu lại và mở trên máy khác.</p>
@if(session('status'))<div class="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="errors" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="editor-wrap"><section class="paper">
<header class="invoice-header">
<div class="invoice-brand"><div class="invoice-logo"><img src="{{ route('draft-invoices.logo', [], false) }}?v={{ filemtime(base_path('public/images/tiba-invoice-logo.png')) }}" alt="Logo Tiba Store" loading="eager" fetchpriority="high"></div><div><h2>TIBA STORE</h2><p>TRANG PHỤC BIỂU DIỄN</p></div></div>
<div class="invoice-heading"><span class="invoice-eyebrow">PHIẾU BÁN HÀNG</span><h3>HÓA ĐƠN</h3>@if($invoice->exists)<span class="invoice-reference">Số phiếu: #{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</span>@endif</div>
</header>
<div class="invoice-meta"><span>THÔNG TIN KHÁCH HÀNG</span><div class="invoice-date">Ngày lập <input type="date" name="invoice_date" aria-label="Ngày hóa đơn" required value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d')) }}"></div></div>
<table class="customer"><tbody><tr><th style="width:24%;text-align:left">Khách hàng:</th><td style="width:36%"><textarea name="customer_name" rows="1" maxlength="255" required aria-label="Khách hàng">{{ old('customer_name', $invoice->customer_name) }}</textarea></td><td><strong>SĐT:</strong><input name="phone" maxlength="50" aria-label="Số điện thoại" value="{{ old('phone', $invoice->phone) }}"></td></tr><tr><td>Địa chỉ</td><td><textarea name="address" rows="3" maxlength="2000" aria-label="Địa chỉ">{{ old('address', $invoice->address) }}</textarea></td><td><strong>Mã số thuế:</strong><input name="tax_code" maxlength="100" aria-label="Mã số thuế" value="{{ old('tax_code', $invoice->tax_code) }}"></td></tr></tbody></table>
<table class="items"><colgroup><col style="width:5%"><col style="width:14%"><col style="width:21%"><col style="width:8%"><col style="width:8%"><col style="width:14%"><col style="width:15%"><col style="width:15%"><col class="no-print" style="width:34px"></colgroup><thead><tr><th>STT</th><th>Mã / Ảnh</th><th>Tên sản phẩm</th><th>ĐVT</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th><th>Ghi chú</th><th class="no-print"></th></tr></thead><tbody id="invoice-items"></tbody><tfoot><tr class="subtotal"><td colspan="6">Tổng tiền hàng</td><td colspan="2" class="money" id="invoice-subtotal">0</td><td class="no-print"></td></tr><tr class="discount-row"><td colspan="6"><div class="discount-controls"><label for="discount-value">Chiết khấu</label><input id="discount-value" name="discount_value" type="number" min="0" step="0.01" value="{{ old('discount_value', $invoice->discount_value ?? 0) }}" aria-label="Giá trị chiết khấu"><select id="discount-type" name="discount_type" aria-label="Loại chiết khấu"><option value="fixed" @selected(old('discount_type', $invoice->discount_type ?? 'fixed') === 'fixed')>đ</option><option value="percent" @selected(old('discount_type', $invoice->discount_type) === 'percent')>%</option></select></div></td><td colspan="2" class="money" id="invoice-discount">0</td><td class="no-print"></td></tr><tr class="total"><td colspan="6">TỔNG THANH TOÁN</td><td colspan="2" class="money" id="invoice-total">0</td><td class="no-print"></td></tr></tfoot></table>
<div class="row-actions no-print"><button type="button" id="add-row">+ Thêm dòng sản phẩm</button></div>
<footer class="invoice-footer"><p class="invoice-thanks">Cảm ơn quý khách đã lựa chọn Tiba Store!</p><div class="invoice-signatures"><div><strong>KHÁCH HÀNG</strong><span>(Ký và ghi rõ họ tên)</span></div><div><strong>NGƯỜI LẬP PHIẾU</strong><span>(Ký và ghi rõ họ tên)</span></div></div><div class="invoice-bottom"><span>TIBA STORE</span><span>Trang phục biểu diễn · Đồng hành cùng sân khấu của bạn</span></div></footer>
</section></div></form>
<template id="invoice-row"><tr><td data-number></td><td class="product-code-cell"><input data-field="code" maxlength="255" aria-label="Mã hàng" placeholder="Nhập mã hàng" autocomplete="off"><img data-product-image hidden alt=""><span class="hint no-print" data-image-message></span></td><td><textarea rows="1" data-field="name" required maxlength="255" aria-label="Tên sản phẩm"></textarea></td><td><input data-field="unit" maxlength="30" aria-label="Đơn vị tính"></td><td><input type="number" data-field="quantity" min="0.01" max="1000000" step="0.01" required aria-label="Số lượng"></td><td><input type="number" data-field="unit_price" min="0" max="1000000000" step="1" required aria-label="Đơn giá (đồng)"></td><td class="money" data-total></td><td><textarea rows="1" data-field="note" maxlength="1000" aria-label="Ghi chú"></textarea></td><td class="no-print"><button type="button" class="delete-row" title="Xóa dòng" aria-label="Xóa dòng">×</button></td></tr></template>
<div id="invoice-product-picker" class="product-picker no-print" hidden>
<div class="picker-head"><strong>Chọn sản phẩm</strong><button type="button" data-picker-close aria-label="Đóng gợi ý">×</button></div>
<label class="picker-category">Danh mục<select id="picker-category" aria-label="Lọc theo danh mục"><option value="">Tất cả danh mục</option></select></label>
<input id="picker-search" type="search" placeholder="Tìm mã, tên hoặc danh mục…" aria-label="Tìm mã, tên hoặc danh mục">
<div id="picker-results" class="picker-results" role="listbox" aria-label="Sản phẩm gợi ý"></div>
<p id="picker-count" class="picker-count" role="status"></p>
</div>
@php($initialItems = old('items', $invoice->items ?? [['name' => '', 'unit' => 'Bộ', 'quantity' => 1, 'unit_price' => 0, 'note' => '']]))
<script>
(function () {
    const initial = @json($initialItems);
    const productCodes = @json($productCodes);
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
        const discountInput = document.getElementById('discount-value');
        const percent = document.getElementById('discount-type').value === 'percent';
        discountInput.max = percent ? 100 : total;
        discountInput.step = percent ? '0.01' : '1';
        const value = Math.max(0, Number(discountInput.value || 0));
        const discount = Math.min(total, Math.round(percent ? total * Math.min(value, 100) / 100 : value));
        document.getElementById('invoice-subtotal').textContent = money.format(total);
        document.getElementById('invoice-discount').textContent = '− ' + money.format(discount);
        document.getElementById('invoice-total').textContent = money.format(total - discount);
        document.getElementById('add-row').disabled = body.rows.length >= 100;
        body.querySelectorAll('.delete-row').forEach(b => b.disabled = body.rows.length <= 1);
    }
    function add(item) {
        const row = document.getElementById('invoice-row').content.cloneNode(true);
        row.querySelectorAll('[data-field]').forEach(input => input.value = item[input.dataset.field] ?? '');
        body.appendChild(row);
        recalculate();
        body.lastElementChild.querySelectorAll('textarea').forEach(resize);
        updateProduct(body.lastElementChild, false);
    }
    function updateProduct(row, fillName) {
        const code = row.querySelector('[data-field="code"]').value.trim().toLocaleUpperCase('vi-VN');
        const product = productCodes.find(item => item.code.trim().toLocaleUpperCase('vi-VN') === code);
        const image = row.querySelector('[data-product-image]');
        const message = row.querySelector('[data-image-message]');
        image.hidden = true;
        image.removeAttribute('src');
        message.textContent = '';
        if (!code) return;
        if (product) {
            const name = row.querySelector('[data-field="name"]');
            if (fillName && !name.value.trim()) { name.value = product.name; resize(name); }
            if (product.image) {
                image.alt = product.name + ' (' + product.code + ')';
                image.onerror = function () { image.hidden = true; message.textContent = 'Không tải được ảnh'; };
                image.src = product.image;
                image.hidden = false;
            } else { message.textContent = 'Mã này chưa có ảnh'; }
        } else { message.textContent = 'Chưa có mã này trong kho'; }
    }
    function changed() { dirty = true; state.textContent = 'Có thay đổi chưa lưu. Bấm Lưu nháp để lưu hóa đơn.'; }
    initial.forEach(add);
    const picker = document.getElementById('invoice-product-picker');
    const pickerSearch = document.getElementById('picker-search');
    const pickerCategory = document.getElementById('picker-category');
    const pickerResults = document.getElementById('picker-results');
    let activeCodeInput = null;
    let activeOption = -1;
    const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase().trim();
    const categories = [...new Set(productCodes.map(product => product.category))].sort((a, b) => a.localeCompare(b, 'vi'));
    categories.forEach(category => { const option = document.createElement('option'); option.value = category; option.textContent = category; pickerCategory.appendChild(option); });
    function closePicker() {
        if (activeCodeInput) activeCodeInput.setAttribute('aria-expanded', 'false');
        picker.hidden = true; activeCodeInput = null;
    }
    function positionPicker() {
        if (!activeCodeInput || picker.hidden) return;
        const rect = activeCodeInput.getBoundingClientRect();
        const gap = 8;
        const width = Math.min(370, window.innerWidth - 2 * gap);
        picker.style.width = width + 'px';
        picker.style.left = Math.max(gap, Math.min(rect.left, window.innerWidth - width - gap)) + 'px';
        const spaceBelow = window.innerHeight - rect.bottom - gap;
        const above = spaceBelow < 280 && rect.top > spaceBelow;
        const available = Math.max(160, (above ? rect.top : window.innerHeight - rect.bottom) - 2 * gap);
        picker.style.maxHeight = Math.min(480, available) + 'px';
        const height = Math.min(picker.scrollHeight, available, 480);
        picker.style.top = Math.max(gap, above ? rect.top - height - gap : rect.bottom + gap) + 'px';
    }
    function matchingProducts(query, category) {
        const term = normalize(query);
        return productCodes.filter(product => (!category || product.category === category) &&
            normalize(product.code + ' ' + product.name + ' ' + product.category).includes(term));
    }
    function choose(product) {
        if (!activeCodeInput) return;
        const input = activeCodeInput;
        const row = input.closest('tr');
        input.value = product.code;
        const name = row.querySelector('[data-field="name"]');
        name.value = product.name; resize(name);
        updateProduct(row, true); recalculate(); changed();
        closePicker(); input.focus({ preventScroll: true });
    }
    function renderProducts() {
        pickerResults.replaceChildren(); activeOption = -1;
        const matches = matchingProducts(pickerSearch.value, pickerCategory.value);
        matches.forEach(product => {
            const button = document.createElement('button'); button.type = 'button'; button.className = 'picker-option'; button.setAttribute('role', 'option');
            const thumbnail = document.createElement('span'); thumbnail.className = 'picker-thumbnail';
            if (product.image) {
                const image = document.createElement('img'); image.src = product.image; image.alt = product.name; image.loading = 'lazy';
                image.onerror = () => { thumbnail.replaceChildren(); thumbnail.textContent = 'Chưa có ảnh'; };
                thumbnail.appendChild(image);
            } else { thumbnail.textContent = 'Chưa có ảnh'; }
            const description = document.createElement('span'); description.className = 'picker-description';
            const code = document.createElement('strong'); code.textContent = product.code;
            const name = document.createElement('span'); name.textContent = product.name;
            const category = document.createElement('small'); category.textContent = product.category;
            description.append(code, name, category); button.append(thumbnail, description);
            button.addEventListener('click', () => choose(product)); pickerResults.appendChild(button);
        });
        if (!matches.length) { const empty = document.createElement('p'); empty.className = 'picker-empty'; empty.textContent = 'Không có sản phẩm phù hợp. Thử mã, tên hoặc danh mục khác.'; pickerResults.appendChild(empty); }
        document.getElementById('picker-count').textContent = matches.length + ' sản phẩm';
        positionPicker();
    }
    function openPicker(input) {
        if (activeCodeInput && activeCodeInput !== input) activeCodeInput.setAttribute('aria-expanded', 'false');
        activeCodeInput = input; picker.hidden = false;
        input.setAttribute('aria-expanded', 'true'); input.setAttribute('aria-controls', 'invoice-product-picker');
        pickerSearch.value = input.value; renderProducts();
    }
    body.addEventListener('click', event => { if (event.target.dataset.field === 'code') openPicker(event.target); });
    body.addEventListener('input', event => { if (event.target.dataset.field === 'code') openPicker(event.target); });
    pickerSearch.addEventListener('input', renderProducts);
    pickerCategory.addEventListener('change', renderProducts);
    picker.querySelector('[data-picker-close]').addEventListener('click', closePicker);
    document.addEventListener('pointerdown', event => { if (!picker.hidden && !picker.contains(event.target) && event.target !== activeCodeInput) closePicker(); });
    document.addEventListener('keydown', event => {
        if (picker.hidden) {
            if (event.key === 'ArrowDown' && event.target.dataset.field === 'code') { event.preventDefault(); openPicker(event.target); }
            return;
        }
        if (event.key === 'Escape') { const input = activeCodeInput; closePicker(); if (input) input.focus({preventScroll:true}); return; }
        if (event.target === pickerCategory) return;
        const options = Array.from(pickerResults.querySelectorAll('.picker-option'));
        if ((event.key === 'ArrowDown' || event.key === 'ArrowUp') && options.length) {
            event.preventDefault(); activeOption = Math.max(0, Math.min(options.length - 1, activeOption + (event.key === 'ArrowDown' ? 1 : -1)));
            options.forEach((option, index) => { option.classList.toggle('is-active', index === activeOption); option.setAttribute('aria-selected', String(index === activeOption)); });
            options[activeOption].scrollIntoView({block:'nearest'});
        } else if (event.key === 'Enter' && (event.target === activeCodeInput || event.target === pickerSearch)) {
            event.preventDefault(); if (options.length) options[Math.max(0, activeOption)].click();
        } else if (event.key === 'Tab' && event.target === activeCodeInput) closePicker();
    });
    window.addEventListener('resize', positionPicker);
    window.addEventListener('scroll', event => { if (!picker.contains(event.target)) positionPicker(); }, true);
    document.getElementById('add-row').addEventListener('click', function () {
        if (body.rows.length >= 100) return;
        add({name:'', unit:'Bộ', quantity:1, unit_price:0, note:''}); changed();
        body.lastElementChild.querySelector('textarea').focus();
    });
    body.addEventListener('click', function (event) { const button = event.target.closest('.delete-row'); if (button && body.rows.length > 1) { button.closest('tr').remove(); recalculate(); changed(); } });
    form.addEventListener('input', function (event) { if (event.target.dataset.field === 'code') updateProduct(event.target.closest('tr'), true); resize(event.target); recalculate(); changed(); });
    function preparePrint() {
        closePicker();
        form.querySelectorAll('.paper input:not([type="hidden"]),.paper textarea,.paper select').forEach(function (input) {
            let span = input.nextElementSibling;
            if (!span || !span.classList.contains('print-value')) { span = document.createElement('span'); span.className = 'print-value'; input.after(span); }
            let value = input.value;
            if (input.type === 'date' && value) value = value.split('-').reverse().join('/');
            if (input.tagName === 'SELECT') value = input.options[input.selectedIndex].text;
            if (input.id === 'discount-value' && document.getElementById('discount-type').value === 'fixed') value = money.format(Number(value));
            if (input.dataset.field === 'unit_price') value = money.format(Number(value));
            span.textContent = value || '\u00a0';
        });
    }
    document.getElementById('discount-type').addEventListener('change', function () { recalculate(); changed(); });
    window.addEventListener('beforeprint', preparePrint);
    document.getElementById('print-invoice').addEventListener('click', function () { if (!form.reportValidity()) return; preparePrint(); window.print(); });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (event) { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
    form.querySelectorAll('textarea').forEach(resize);
})();
</script></main></div></body></html>
