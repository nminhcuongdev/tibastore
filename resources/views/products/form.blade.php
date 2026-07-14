<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'create' ? 'Thêm sản phẩm' : 'Sửa sản phẩm' }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #fff7f9;
            color: #3f2730;
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .topbar {
            align-items: center;
            background: #fff;
            border-bottom: 1px solid #f5d9e2;
            display: flex;
            gap: 20px;
            justify-content: space-between;
            padding: 18px clamp(18px, 5vw, 56px);
        }

        .brand {
            color: #b63f68;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 28px;
            font-weight: 700;
        }

        .content {
            padding: 36px clamp(18px, 5vw, 56px) 56px;
        }

        .heading {
            margin-bottom: 22px;
        }

        .heading h1 {
            color: #6f253f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(32px, 4vw, 48px);
            line-height: 1.08;
            margin: 0 0 8px;
        }

        .heading p {
            color: #704252;
            margin: 0;
        }

        .form-shell {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
            display: grid;
            gap: 24px;
            grid-template-columns: minmax(0, 1fr) 240px;
            max-width: 980px;
            padding: 24px;
        }

        .fields {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .field {
            display: grid;
            gap: 7px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .size-rows {
            display: grid;
            gap: 12px;
        }

        .size-row {
            background: #fffafb;
            border: 1px solid #f2d3dc;
            border-radius: 8px;
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1fr) 150px auto;
            padding: 14px;
        }

        .expected-receipts {
            background: #fff;
            border: 1px solid #f2d3dc;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-column: 1 / -1;
            padding: 12px;
        }

        .expected-receipts-head {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .expected-receipt-rows {
            display: grid;
            gap: 8px;
        }

        .expected-receipt-row {
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(0, 1fr) 150px auto;
        }

        .button.icon {
            min-height: 44px;
            padding: 10px 14px;
        }

        label {
            color: #7a344c;
            font-size: 13px;
            font-weight: 800;
        }

        input {
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 8px;
            color: #3f2730;
            font-size: 15px;
            min-height: 44px;
            padding: 10px 13px;
            width: 100%;
        }

        input:focus {
            border-color: #c9577d;
            box-shadow: 0 0 0 3px rgba(201, 87, 125, .16);
            outline: none;
        }

        .error {
            color: #b4233f;
            font-size: 13px;
            font-weight: 700;
        }

        .hint {
            color: #8b6672;
            font-size: 12px;
        }

        .hint.warn {
            color: #b4233f;
            font-weight: 700;
        }

        .code-suggest-wrap {
            position: relative;
        }

        .code-suggestions {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 14px 36px rgba(117, 44, 69, .08);
            display: none;
            left: 0;
            margin-top: 6px;
            max-height: 240px;
            overflow-y: auto;
            position: absolute;
            right: 0;
            top: 100%;
            z-index: 5;
        }

        .code-suggestion {
            background: transparent;
            border: 0;
            border-bottom: 1px solid #f7e3e9;
            cursor: pointer;
            display: block;
            padding: 9px 12px;
            text-align: left;
            width: 100%;
        }

        .code-suggestion:hover {
            background: #fff4f7;
        }

        .code-suggestion-main {
            color: #3f2730;
            font-weight: 800;
        }

        .code-suggestion-next {
            color: #a13b60;
        }

        .code-suggestion-meta {
            color: #8b6672;
            font-size: 12px;
            margin-top: 2px;
        }

        .code-suggest-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .button.small {
            min-height: 38px;
            padding: 8px 12px;
        }

        .preview {
            align-content: center;
            background: #fff4f7;
            border: 1px dashed #e8aebe;
            border-radius: 8px;
            color: #a64465;
            display: grid;
            font-weight: 800;
            min-height: 240px;
            overflow: hidden;
            text-align: center;
        }

        .preview img {
            height: 100%;
            object-fit: cover;
            width: 100%;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            grid-column: 1 / -1;
            justify-content: flex-end;
            margin-top: 4px;
        }

        .button {
            align-items: center;
            background: #be476f;
            border: 0;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            font-weight: 800;
            min-height: 44px;
            padding: 10px 16px;
        }

        .button.secondary {
            background: #fff;
            border: 1px solid #ebc5d2;
            color: #8b2f4d;
        }

        .button.danger {
            background: #fff;
            border: 1px solid #f0b7c1;
            color: #b4233f;
        }

        @media (max-width: 760px) {
            .form-shell,
            .fields,
            .size-row,
            .expected-receipt-row {
                grid-template-columns: 1fr;
            }

            .actions {
                justify-content: stretch;
            }

            .button {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('products.index') }}">Tiba Boutique</a>
        <a class="button secondary" href="{{ route('products.index') }}">Về danh sách</a>
    </header>

    <main class="content">
        <div class="heading">
            <h1>{{ $mode === 'create' ? 'Thêm sản phẩm mới' : 'Sửa thông tin sản phẩm' }}</h1>
            <p>Cập nhật thông tin tồn kho, chất liệu vải, size và giá nhập cho từng mẫu thời trang.</p>
        </div>

        <form class="form-shell" method="POST" action="{{ $mode === 'create' ? route('products.store') : route('products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @if ($mode === 'edit')
                @method('PUT')
            @endif
            @if ($mode === 'create' && ! empty($sourceProductId))
                <input type="hidden" name="source_product_id" value="{{ $sourceProductId }}">
            @endif

            <div class="fields">
                <div class="field">
                    <label for="code">Mã sản phẩm</label>
                    @if ($mode === 'create')
                        <div class="code-suggest-wrap">
                            <input id="code" name="code" type="text" value="{{ old('code', $product->code) }}" maxlength="50" autocomplete="off" required>
                            <div id="code_suggestions" class="code-suggestions"></div>
                        </div>
                        <div class="code-suggest-actions">
                            <button id="suggest_next_code" class="button secondary small" type="button">Gợi ý mã tiếp theo</button>
                            <span id="code_dup_hint" class="hint"></span>
                        </div>
                        <div class="hint">Gõ vài ký tự để xem mã đã có và chọn mã mới chỉ khác ở đuôi.</div>
                    @else
                        <input id="code" name="code" type="text" value="{{ old('code', $product->code) }}" maxlength="50" required>
                        <div class="hint">Đổi mã sẽ áp dụng cho tất cả size thuộc mã hàng này.</div>
                    @endif
                    @error('code') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="name">Tên sản phẩm</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}" required>
                    @error('name') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="fabric">Vải</label>
                    <input id="fabric" name="fabric" type="text" value="{{ old('fabric', $product->fabric) }}" placeholder="Lụa, cotton, tweed..." required>
                    @error('fabric') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="category">Danh mục</label>
                    <input id="category" name="category" type="text" value="{{ old('category', $product->category) }}" placeholder="Váy dạ hội, áo dài, vest...">
                    @error('category') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="rental_price">Giá thuê</label>
                    <input id="rental_price" name="rental_price" type="number" min="0" step="1000" value="{{ old('rental_price', $product->rental_price ?? 0) }}">
                    @error('rental_price') <div class="error">{{ $message }}</div> @enderror
                </div>

                @if ($mode === 'create')
                    @php
                        $sourceReceiptRows = ($sourceExpectedReceipts ?? collect())
                            ->map(fn ($receipt) => [
                                'expected_receive_date' => $receipt->expected_receive_date?->format('Y-m-d'),
                                'expected_receive_quantity' => $receipt->expected_receive_quantity,
                            ])
                            ->values()
                            ->all();
                        $defaultExpectedReceipts = $sourceReceiptRows !== []
                            ? $sourceReceiptRows
                            : [[
                                'expected_receive_date' => '',
                                'expected_receive_quantity' => 0,
                            ]];
                        $variantRows = old('variants', [[
                            'size' => '',
                            'stock_quantity' => 0,
                            'expected_receipts' => $defaultExpectedReceipts,
                        ]]);
                    @endphp

                    <div class="field full">
                        <label>Size và số lượng</label>
                        <div id="size_rows" class="size-rows">
                            @foreach ($variantRows as $index => $variant)
                                <div class="size-row">
                                    <div class="field">
                                        <label for="variants_{{ $index }}_size">Size</label>
                                        <input id="variants_{{ $index }}_size" data-size name="variants[{{ $index }}][size]" type="text" value="{{ $variant['size'] ?? '' }}" placeholder="S, M, L, XL, Free size..." required>
                                    </div>
                                    <div class="field">
                                        <label for="variants_{{ $index }}_stock_quantity">Số lượng</label>
                                        <input id="variants_{{ $index }}_stock_quantity" data-stock name="variants[{{ $index }}][stock_quantity]" type="number" min="0" step="1" value="{{ $variant['stock_quantity'] ?? 0 }}" placeholder="0">
                                    </div>
                                    <div class="field">
                                        <label>&nbsp;</label>
                                        <button class="button danger" data-remove-size type="button">Xóa</button>
                                    </div>
                                    <div class="expected-receipts" data-expected-receipts>
                                        <div class="expected-receipts-head">
                                            <label>Dự kiến nhận hàng</label>
                                            <button class="button secondary icon" data-add-expected-receipt type="button">+</button>
                                        </div>
                                        <div class="expected-receipt-rows" data-expected-receipt-rows>
                                            @foreach (($variant['expected_receipts'] ?? $defaultExpectedReceipts) as $receiptIndex => $receipt)
                                                <div class="expected-receipt-row" data-expected-receipt-row>
                                                    <input data-expected-date name="variants[{{ $index }}][expected_receipts][{{ $receiptIndex }}][expected_receive_date]" type="date" value="{{ $receipt['expected_receive_date'] ?? '' }}">
                                                    <input data-expected-quantity name="variants[{{ $index }}][expected_receipts][{{ $receiptIndex }}][expected_receive_quantity]" type="number" min="0" step="1" value="{{ $receipt['expected_receive_quantity'] ?? 0 }}" placeholder="SL dự kiến">
                                                    <button class="button danger icon" data-remove-expected-receipt type="button">-</button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button id="add_size_button" class="button secondary" type="button">Thêm size</button>
                        @error('variants') <div class="error">{{ $message }}</div> @enderror
                        @error('variants.*.size') <div class="error">{{ $message }}</div> @enderror
                        @error('variants.*.stock_quantity') <div class="error">{{ $message }}</div> @enderror
                        @error('variants.*.expected_receipts.*.expected_receive_date') <div class="error">{{ $message }}</div> @enderror
                        @error('variants.*.expected_receipts.*.expected_receive_quantity') <div class="error">{{ $message }}</div> @enderror
                    </div>
                @else
                    @php
                        $expectedReceiptRows = old(
                            'expected_receipts',
                            $product->expectedReceipts
                                ->whereNull('received_at')
                                ->map(fn ($receipt) => [
                                    'expected_receive_date' => $receipt->expected_receive_date?->format('Y-m-d'),
                                    'expected_receive_quantity' => $receipt->expected_receive_quantity,
                                ])
                                ->values()
                                ->all()
                        );

                        if ($expectedReceiptRows === []) {
                            $expectedReceiptRows = [[
                                'expected_receive_date' => '',
                                'expected_receive_quantity' => 0,
                            ]];
                        }
                    @endphp

                    <div class="field">
                        <label for="stock_quantity">Số lượng tồn</label>
                        <input id="stock_quantity" name="stock_quantity" type="number" min="0" step="1" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" placeholder="0">
                        @error('stock_quantity') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label for="size">Size</label>
                        <input id="size" name="size" type="text" value="{{ old('size', $product->size) }}" placeholder="S, M, L, XL, Free size..." required>
                        @error('size') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field full">
                        <div class="expected-receipts" data-expected-receipts>
                            <div class="expected-receipts-head">
                                <label>Dự kiến nhận hàng</label>
                                <button class="button secondary icon" data-add-expected-receipt type="button">+</button>
                            </div>
                            <div class="expected-receipt-rows" data-expected-receipt-rows>
                                @foreach ($expectedReceiptRows as $receiptIndex => $receipt)
                                    <div class="expected-receipt-row" data-expected-receipt-row>
                                        <input data-expected-date name="expected_receipts[{{ $receiptIndex }}][expected_receive_date]" type="date" value="{{ $receipt['expected_receive_date'] ?? '' }}">
                                        <input data-expected-quantity name="expected_receipts[{{ $receiptIndex }}][expected_receive_quantity]" type="number" min="0" step="1" value="{{ $receipt['expected_receive_quantity'] ?? 0 }}" placeholder="SL dự kiến">
                                        <button class="button danger icon" data-remove-expected-receipt type="button">-</button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @error('expected_receipts.*.expected_receive_date') <div class="error">{{ $message }}</div> @enderror
                        @error('expected_receipts.*.expected_receive_quantity') <div class="error">{{ $message }}</div> @enderror
                    </div>
                @endif

                <div class="field">
                    <label for="image">Hình ảnh</label>
                    <input id="image" name="image" type="file" accept="image/*">
                    @error('image') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="preview">
                @if ($product->image_path)
                    <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}">
                @else
                    Chưa có hình ảnh
                @endif
            </div>

            <div class="actions">
                <a class="button secondary" href="{{ route('products.index') }}">Hủy</a>
                <button class="button" type="submit">{{ $mode === 'create' ? 'Thêm sản phẩm' : 'Lưu thay đổi' }}</button>
            </div>
        </form>
    </main>
    <script>
        const mode = @json($mode);
        const existingCodes = @json($existingCodes ?? [], JSON_UNESCAPED_UNICODE);
        const sizeRows = document.getElementById('size_rows');
        const addSizeButton = document.getElementById('add_size_button');

        function addExpectedReceiptRow(container, variantIndex = null) {
            const rows = container.querySelector('[data-expected-receipt-rows]');
            const row = document.createElement('div');

            row.className = 'expected-receipt-row';
            row.dataset.expectedReceiptRow = '';
            row.innerHTML = `
                <input data-expected-date type="date">
                <input data-expected-quantity type="number" min="0" step="1" placeholder="SL dự kiến">
                <button class="button danger icon" data-remove-expected-receipt type="button">-</button>
            `;

            rows.appendChild(row);
            bindExpectedReceiptRow(row);
            renumberExpectedReceipts(container, variantIndex);
            row.querySelector('[data-expected-date]').focus();
        }

        function bindExpectedReceiptRow(row) {
            row.querySelector('[data-remove-expected-receipt]').addEventListener('click', () => {
                const container = row.closest('[data-expected-receipts]');
                const rows = container.querySelectorAll('[data-expected-receipt-row]');

                if (rows.length === 1) {
                    row.querySelector('[data-expected-date]').value = '';
                    row.querySelector('[data-expected-quantity]').value = '';
                    return;
                }

                row.remove();
                renumberExpectedReceipts(container);
            });
        }

        function renumberExpectedReceipts(container, variantIndex = null) {
            const sizeRow = container.closest('.size-row');
            const resolvedVariantIndex = variantIndex ?? (sizeRow ? Array.from(sizeRows.querySelectorAll('.size-row')).indexOf(sizeRow) : null);

            container.querySelectorAll('[data-expected-receipt-row]').forEach((row, index) => {
                const dateInput = row.querySelector('[data-expected-date]');
                const quantityInput = row.querySelector('[data-expected-quantity]');

                if (mode === 'create') {
                    dateInput.name = `variants[${resolvedVariantIndex}][expected_receipts][${index}][expected_receive_date]`;
                    quantityInput.name = `variants[${resolvedVariantIndex}][expected_receipts][${index}][expected_receive_quantity]`;
                    return;
                }

                dateInput.name = `expected_receipts[${index}][expected_receive_date]`;
                quantityInput.name = `expected_receipts[${index}][expected_receive_quantity]`;
            });
        }

        function bindExpectedReceipts(container) {
            container.querySelector('[data-add-expected-receipt]').addEventListener('click', () => addExpectedReceiptRow(container));
            container.querySelectorAll('[data-expected-receipt-row]').forEach(bindExpectedReceiptRow);
            renumberExpectedReceipts(container);
        }

        document.querySelectorAll('[data-expected-receipts]').forEach(bindExpectedReceipts);

        if (mode === 'create') {
            function renumberSizeRows() {
                sizeRows.querySelectorAll('.size-row').forEach((row, index) => {
                    const sizeInput = row.querySelector('[data-size]');
                    const stockInput = row.querySelector('[data-stock]');

                    sizeInput.name = `variants[${index}][size]`;
                    sizeInput.id = `variants_${index}_size`;
                    stockInput.name = `variants[${index}][stock_quantity]`;
                    stockInput.id = `variants_${index}_stock_quantity`;
                    renumberExpectedReceipts(row.querySelector('[data-expected-receipts]'), index);
                });
            }

            function bindRemoveSizeButton(row) {
                row.querySelector('[data-remove-size]').addEventListener('click', () => {
                    if (sizeRows.querySelectorAll('.size-row').length === 1) {
                        return;
                    }

                    row.remove();
                    renumberSizeRows();
                });
            }

            sizeRows.querySelectorAll('.size-row').forEach(bindRemoveSizeButton);

            addSizeButton.addEventListener('click', () => {
                const index = sizeRows.querySelectorAll('.size-row').length;
                const row = document.createElement('div');

                row.className = 'size-row';
                row.innerHTML = `
                    <div class="field">
                        <label for="variants_${index}_size">Size</label>
                        <input id="variants_${index}_size" data-size name="variants[${index}][size]" type="text" placeholder="S, M, L, XL, Free size..." required>
                    </div>
                    <div class="field">
                        <label for="variants_${index}_stock_quantity">Số lượng</label>
                        <input id="variants_${index}_stock_quantity" data-stock name="variants[${index}][stock_quantity]" type="number" min="0" step="1" placeholder="0">
                    </div>
                    <div class="field">
                        <label>&nbsp;</label>
                        <button class="button danger" data-remove-size type="button">Xóa</button>
                    </div>
                    <div class="expected-receipts" data-expected-receipts>
                        <div class="expected-receipts-head">
                            <label>Dự kiến nhận hàng</label>
                            <button class="button secondary icon" data-add-expected-receipt type="button">+</button>
                        </div>
                        <div class="expected-receipt-rows" data-expected-receipt-rows>
                            <div class="expected-receipt-row" data-expected-receipt-row>
                                <input data-expected-date type="date">
                                <input data-expected-quantity type="number" min="0" step="1" placeholder="SL dự kiến">
                                <button class="button danger icon" data-remove-expected-receipt type="button">-</button>
                            </div>
                        </div>
                    </div>
                `;

                bindRemoveSizeButton(row);
                bindExpectedReceipts(row.querySelector('[data-expected-receipts]'));
                sizeRows.appendChild(row);
                renumberSizeRows();
                row.querySelector('[data-size]').focus();
            });
        }

        if (mode === 'create') {
            const codeInput = document.getElementById('code');
            const codeSuggestions = document.getElementById('code_suggestions');
            const suggestNextButton = document.getElementById('suggest_next_code');
            const codeDupHint = document.getElementById('code_dup_hint');
            const codeMap = new Map(existingCodes.map(item => [item.code.toLowerCase(), item]));

            function codeExists(code) {
                return codeMap.has(String(code).trim().toLowerCase());
            }

            // Tạo mã kế tiếp bằng cách tăng phần số ở đuôi, giữ nguyên độ rộng (số 0 đệm),
            // và bỏ qua những mã đã tồn tại để tránh trùng.
            function nextCodeFrom(base) {
                const trimmed = String(base || '').trim();
                const match = trimmed.match(/^(.*?)(\d+)(\D*)$/);
                let candidate;

                if (match) {
                    const prefix = match[1];
                    const width = match[2].length;
                    const suffix = match[3];
                    let number = parseInt(match[2], 10);

                    do {
                        number += 1;
                        candidate = `${prefix}${String(number).padStart(width, '0')}${suffix}`;
                    } while (codeExists(candidate) && number < 1000000);

                    return candidate;
                }

                let number = 0;

                do {
                    number += 1;
                    candidate = `${trimmed}${String(number).padStart(2, '0')}`;
                } while (codeExists(candidate) && number < 1000);

                return candidate;
            }

            function enteredSizes() {
                return Array.from(document.querySelectorAll('#size_rows [data-size]'))
                    .map(input => input.value.trim())
                    .filter(Boolean);
            }

            function updateCodeDupHint() {
                const found = codeMap.get(codeInput.value.trim().toLowerCase());

                if (! found) {
                    codeDupHint.textContent = '';
                    codeDupHint.classList.remove('warn');
                    return;
                }

                const takenSizes = found.sizes.map(size => size.toLowerCase());
                const clashing = enteredSizes().filter(size => takenSizes.includes(size.toLowerCase()));

                if (clashing.length > 0) {
                    codeDupHint.textContent = `Mã ${found.code} đã có size: ${clashing.join(', ')}. Hãy đổi đuôi mã hoặc dùng size khác.`;
                    codeDupHint.classList.add('warn');
                } else {
                    codeDupHint.textContent = `Mã ${found.code} đã tồn tại (size: ${found.sizes.join(', ')}).`;
                    codeDupHint.classList.remove('warn');
                }
            }

            function renderCodeSuggestions() {
                const keyword = codeInput.value.trim().toLowerCase();
                const matches = existingCodes
                    .filter(item => keyword === '' || item.code.toLowerCase().includes(keyword))
                    .slice(0, 20);

                codeSuggestions.innerHTML = '';

                if (matches.length === 0) {
                    codeSuggestions.style.display = 'none';
                    return;
                }

                matches.forEach(item => {
                    const next = nextCodeFrom(item.code);
                    const button = document.createElement('button');
                    const main = document.createElement('div');
                    const nextSpan = document.createElement('span');
                    const meta = document.createElement('div');

                    button.type = 'button';
                    button.className = 'code-suggestion';
                    main.className = 'code-suggestion-main';
                    main.textContent = `${item.code} → `;
                    nextSpan.className = 'code-suggestion-next';
                    nextSpan.textContent = next;
                    main.appendChild(nextSpan);
                    meta.className = 'code-suggestion-meta';
                    meta.textContent = item.sizes.length
                        ? `Đã có ${item.sizes.length} size: ${item.sizes.join(', ')}`
                        : 'Chưa có size';
                    button.append(main, meta);
                    button.addEventListener('click', () => {
                        codeInput.value = next;
                        codeSuggestions.style.display = 'none';
                        updateCodeDupHint();
                        codeInput.focus();
                    });
                    codeSuggestions.appendChild(button);
                });

                codeSuggestions.style.display = 'block';
            }

            codeInput.addEventListener('input', () => {
                renderCodeSuggestions();
                updateCodeDupHint();
            });

            codeInput.addEventListener('focus', renderCodeSuggestions);

            suggestNextButton.addEventListener('click', () => {
                const base = codeInput.value.trim()
                    || (existingCodes.length ? existingCodes[existingCodes.length - 1].code : '');

                if (! base) {
                    codeInput.focus();
                    return;
                }

                codeInput.value = nextCodeFrom(base);
                codeSuggestions.style.display = 'none';
                updateCodeDupHint();
                codeInput.focus();
            });

            document.addEventListener('click', event => {
                if (! codeInput.closest('.code-suggest-wrap').contains(event.target)) {
                    codeSuggestions.style.display = 'none';
                }
            });

            sizeRows.addEventListener('input', event => {
                if (event.target.matches('[data-size]')) {
                    updateCodeDupHint();
                }
            });

            updateCodeDupHint();
        }
    </script>
</body>
</html>


