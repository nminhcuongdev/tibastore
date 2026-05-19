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
            .size-row {
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
                    <input id="code" name="code" type="text" value="{{ old('code', $product->code) }}" maxlength="50" required>
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
                    <label for="import_price">Giá nhập</label>
                    <input id="import_price" name="import_price" type="number" min="0" step="1000" value="{{ old('import_price', $product->import_price ?? 0) }}" required>
                    @error('import_price') <div class="error">{{ $message }}</div> @enderror
                </div>

                @if ($mode === 'create')
                    @php
                        $variantRows = old('variants', [['size' => '', 'stock_quantity' => 0]]);
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
                                </div>
                            @endforeach
                        </div>
                        <button id="add_size_button" class="button secondary" type="button">Thêm size</button>
                        @error('variants') <div class="error">{{ $message }}</div> @enderror
                        @error('variants.*.size') <div class="error">{{ $message }}</div> @enderror
                        @error('variants.*.stock_quantity') <div class="error">{{ $message }}</div> @enderror
                    </div>
                @else
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
    @if ($mode === 'create')
        <script>
            const sizeRows = document.getElementById('size_rows');
            const addSizeButton = document.getElementById('add_size_button');

            function renumberSizeRows() {
                sizeRows.querySelectorAll('.size-row').forEach((row, index) => {
                    const sizeInput = row.querySelector('[data-size]');
                    const stockInput = row.querySelector('[data-stock]');

                    sizeInput.name = `variants[${index}][size]`;
                    sizeInput.id = `variants_${index}_size`;
                    stockInput.name = `variants[${index}][stock_quantity]`;
                    stockInput.id = `variants_${index}_stock_quantity`;
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
                `;

                bindRemoveSizeButton(row);
                sizeRows.appendChild(row);
                row.querySelector('[data-size]').focus();
            });
        </script>
    @endif
</body>
</html>


