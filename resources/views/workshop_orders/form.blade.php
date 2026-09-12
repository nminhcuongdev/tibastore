<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'create' ? 'Thêm dòng đặt xưởng' : 'Sửa dòng đặt xưởng' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            background: #fff7f9;
            color: #3f2730;
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        .content { flex: 1; min-width: 0; padding: 32px clamp(18px, 4vw, 48px) 56px; }
        .heading { margin-bottom: 20px; }
        .heading h1 {
            color: #6f253f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(26px, 3.4vw, 38px);
            line-height: 1.1;
            margin: 0 0 8px;
        }
        .heading p { color: #704252; margin: 0; }
        .form-shell {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            max-width: 900px;
            padding: 24px;
        }
        .field { display: grid; gap: 7px; }
        .field.full { grid-column: 1 / -1; }
        label { color: #7a344c; font-size: 13px; font-weight: 800; }
        .hint { color: #8b6672; font-size: 12px; font-weight: 600; }
        input, select, textarea {
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 8px;
            color: #3f2730;
            font-family: inherit;
            font-size: 15px;
            min-height: 44px;
            padding: 10px 13px;
            width: 100%;
        }
        textarea { min-height: 84px; resize: vertical; }
        input:focus, select:focus, textarea:focus {
            border-color: #c9577d;
            box-shadow: 0 0 0 3px rgba(201, 87, 125, .16);
            outline: none;
        }
        .error { color: #b4233f; font-size: 13px; font-weight: 700; }

        /* ===== Ô chọn mã hàng kèm ảnh ===== */
        .picker { min-width: 0; position: relative; }
        .suggestions {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 14px 36px rgba(117, 44, 69, .14);
            display: none;
            left: 0;
            margin-top: 6px;
            max-height: 320px;
            overflow-y: auto;
            position: absolute;
            right: 0;
            top: 100%;
            z-index: 30;
        }
        .suggestion {
            align-items: center;
            background: transparent;
            border: 0;
            border-bottom: 1px solid #f7e3e9;
            cursor: pointer;
            display: flex;
            gap: 12px;
            padding: 9px 11px;
            text-align: left;
            width: 100%;
        }
        .suggestion:hover, .suggestion.is-active { background: #fff4f7; }
        .suggestion .s-thumb {
            align-items: center;
            background: #f9e5ec;
            border: 1px solid #f1cbd7;
            border-radius: 6px;
            color: #a64465;
            display: flex;
            flex: 0 0 auto;
            font-size: 9px;
            font-weight: 800;
            height: 52px;
            justify-content: center;
            overflow: hidden;
            text-align: center;
            width: 52px;
        }
        .suggestion .s-thumb img { height: 100%; object-fit: cover; width: 100%; }
        .suggestion .s-code { color: #a13b60; font-weight: 900; }
        .suggestion .s-name { color: #8b6672; font-size: 13px; }
        .no-match { color: #8b6672; font-size: 13px; padding: 14px; }

        /* ===== Preview mã đã chọn ===== */
        .preview {
            align-items: center;
            background: #fff4f7;
            border: 1px solid #f2d3dc;
            border-radius: 8px;
            color: #704252;
            display: none;
            gap: 14px;
            grid-column: 1 / -1;
            padding: 14px;
        }
        .preview.is-on { display: flex; }
        .preview .p-thumb {
            align-items: center;
            background: #f9e5ec;
            border: 1px solid #f1cbd7;
            border-radius: 8px;
            color: #a64465;
            cursor: zoom-in;
            display: flex;
            flex: 0 0 auto;
            font-size: 10px;
            font-weight: 800;
            height: 104px;
            justify-content: center;
            overflow: hidden;
            padding: 0;
            text-align: center;
            width: 104px;
        }
        .preview .p-thumb img { height: 100%; object-fit: cover; width: 100%; }
        .preview .p-code { color: #a13b60; font-size: 17px; font-weight: 900; }
        .preview .p-name { font-weight: 700; }
        .preview .p-warn { color: #b4233f; font-weight: 800; }

        .image-lightbox {
            align-items: center;
            background: rgba(63, 39, 48, .72);
            cursor: zoom-out;
            display: none;
            inset: 0;
            justify-content: center;
            padding: 24px;
            position: fixed;
            z-index: 1000;
        }
        .image-lightbox.is-open { display: flex; }
        .image-lightbox img {
            background: #fff;
            border: 8px solid #fff;
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
            max-height: min(82vh, 760px);
            max-width: min(88vw, 760px);
            object-fit: contain;
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
        .button.secondary { background: #fff; border: 1px solid #ebc5d2; color: #8b2f4d; }
        .flash {
            background: #fff;
            border: 1px solid #f0c7d3;
            border-left: 5px solid #b4233f;
            border-radius: 8px;
            color: #b4233f;
            margin-bottom: 16px;
            max-width: 900px;
            padding: 12px 14px;
        }
        @media (max-width: 760px) {
            .form-shell { grid-template-columns: 1fr; }
            .button { justify-content: center; width: 100%; }
        }
    </style>
    @include('partials.compact')
</head>
<body>
<div class="layout">
@include('partials.sidebar', ['active' => 'workshop'])
<main class="content">
    <div class="heading">
        <h1>{{ $mode === 'create' ? 'Thêm dòng đặt xưởng' : 'Sửa dòng đặt xưởng' }}</h1>
        <p>Mã hàng phải là mã đã có trong kho; tên và ảnh sẽ tự lấy theo mã đó.</p>
    </div>

    @if ($errors->any())
        <div class="flash">
            <strong>Chưa lưu được — vui lòng kiểm tra:</strong>
            <ul style="margin: 6px 0 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="form-shell" method="POST"
        action="{{ $mode === 'create' ? route('workshop-orders.store') : route('workshop-orders.update', $workshopOrder) }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="field">
            <label for="product_code">Mã hàng</label>
            <div class="picker">
                <input id="product_code" name="product_code" type="text" autocomplete="off" required
                    value="{{ old('product_code', $workshopOrder->product_code) }}"
                    placeholder="Gõ để tìm mã trong kho..." data-code-input>
                <div class="suggestions" data-suggestions></div>
            </div>
            <span class="hint">Gõ mã hoặc tên, danh sách gợi ý có sẵn ảnh để bạn chọn cho đúng.</span>
            @error('product_code') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="size_note">Size</label>
            <input id="size_note" name="size_note" type="text"
                value="{{ old('size_note', $workshopOrder->size_note) }}" placeholder="VD: 5s 5m 2L 3xl">
            <span class="hint">Ghi tự do số lượng theo từng size.</span>
            @error('size_note') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="preview" data-preview>
            <button class="p-thumb" type="button" data-preview-thumb title="Bấm để xem ảnh lớn">
                <img src="" alt="" data-preview-image>
                <span data-preview-noimage style="display: none;">CHƯA CÓ ẢNH</span>
            </button>
            <div>
                <div class="p-code" data-preview-code></div>
                <div class="p-name" data-preview-name></div>
                <div class="p-warn" data-preview-warn style="display: none;">Mã này chưa có trong kho.</div>
            </div>
        </div>

        <div class="field">
            <label for="order_name">Tên đơn</label>
            <input id="order_name" name="order_name" type="text"
                value="{{ old('order_name', $workshopOrder->order_name) }}" placeholder="Đơn cần hàng này (nếu có)">
            @error('order_name') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="source">Nguồn đặt</label>
            <input id="source" name="source" type="text"
                value="{{ old('source', $workshopOrder->source) }}" placeholder="VD: Huyền Trang">
            @error('source') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="ordered_date">Ngày đặt hàng</label>
            <input id="ordered_date" name="ordered_date" type="date"
                value="{{ old('ordered_date', $workshopOrder->ordered_date?->format('Y-m-d')) }}">
            @error('ordered_date') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="returned_date">Ngày trả hàng</label>
            <input id="returned_date" name="returned_date" type="date"
                value="{{ old('returned_date', $workshopOrder->returned_date?->format('Y-m-d')) }}">
            @error('returned_date') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="buyer">Người mua hàng</label>
            <input id="buyer" name="buyer" type="text"
                value="{{ old('buyer', $workshopOrder->buyer) }}" placeholder="VD: chị Thanh">
            @error('buyer') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="status">Xưởng đã trả chưa</label>
            <select id="status" name="status" required>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}"
                        @selected(old('status', $workshopOrder->status ?? \App\Models\WorkshopOrder::DEFAULT_STATUS) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field full">
            <label for="buyer_note">Người mua hàng ghi chú</label>
            <textarea id="buyer_note" name="buyer_note" placeholder="Ghi chú thêm cho người mua hàng...">{{ old('buyer_note', $workshopOrder->buyer_note) }}</textarea>
            @error('buyer_note') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="actions">
            <a class="button secondary" href="{{ route('workshop-orders.index') }}">Hủy</a>
            <button class="button" type="submit">{{ $mode === 'create' ? 'Thêm dòng' : 'Lưu thay đổi' }}</button>
        </div>
    </form>
</main>
</div>

<div class="image-lightbox" data-lightbox>
    <img src="" alt="Ảnh sản phẩm" data-lightbox-image>
</div>

<script>
    (function () {
        const PRODUCTS = @json($productCodes);

        const input = document.querySelector('[data-code-input]');
        const box = document.querySelector('[data-suggestions]');
        const preview = document.querySelector('[data-preview]');
        const pThumb = preview.querySelector('[data-preview-thumb]');
        const pImage = preview.querySelector('[data-preview-image]');
        const pNoImage = preview.querySelector('[data-preview-noimage]');
        const pCode = preview.querySelector('[data-preview-code]');
        const pName = preview.querySelector('[data-preview-name]');
        const pWarn = preview.querySelector('[data-preview-warn]');
        const lightbox = document.querySelector('[data-lightbox]');
        const lightboxImage = lightbox.querySelector('[data-lightbox-image]');

        let activeIndex = -1;

        function findByCode(code) {
            const wanted = (code || '').trim().toLowerCase();
            return PRODUCTS.find(item => item.code.toLowerCase() === wanted) || null;
        }

        // Preview đổi theo mã đang gõ, kể cả khi gõ tay thay vì bấm gợi ý.
        function renderPreview() {
            const typed = input.value.trim();

            if (typed === '') {
                preview.classList.remove('is-on');
                return;
            }

            const found = findByCode(typed);
            preview.classList.add('is-on');

            if (!found) {
                pCode.textContent = typed;
                pName.textContent = '';
                pWarn.style.display = 'block';
                pImage.style.display = 'none';
                pNoImage.style.display = 'block';
                pNoImage.textContent = 'KHÔNG RÕ';
                pThumb.style.cursor = 'default';
                return;
            }

            pCode.textContent = found.code;
            pName.textContent = found.name || '';
            pWarn.style.display = 'none';

            if (found.image) {
                pImage.src = found.image;
                pImage.alt = found.code;
                pImage.style.display = 'block';
                pNoImage.style.display = 'none';
                pThumb.style.cursor = 'zoom-in';
            } else {
                pImage.style.display = 'none';
                pNoImage.style.display = 'block';
                pNoImage.textContent = 'CHƯA CÓ ẢNH';
                pThumb.style.cursor = 'default';
            }
        }

        function closeSuggestions() {
            box.style.display = 'none';
            activeIndex = -1;
        }

        function choose(code) {
            input.value = code;
            closeSuggestions();
            renderPreview();
        }

        function renderSuggestions() {
            const keyword = input.value.trim().toLowerCase();
            const matches = PRODUCTS.filter(item =>
                item.code.toLowerCase().includes(keyword)
                || (item.name || '').toLowerCase().includes(keyword)
            ).slice(0, 30);

            box.innerHTML = '';
            activeIndex = -1;

            if (matches.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'no-match';
                empty.textContent = 'Không tìm thấy mã nào trong kho.';
                box.appendChild(empty);
                box.style.display = 'block';
                return;
            }

            matches.forEach(item => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'suggestion';

                const thumb = document.createElement('span');
                thumb.className = 's-thumb';
                if (item.image) {
                    const img = document.createElement('img');
                    img.src = item.image;
                    img.alt = item.code;
                    img.loading = 'lazy';
                    thumb.appendChild(img);
                } else {
                    thumb.textContent = 'CHƯA CÓ ẢNH';
                }

                const texts = document.createElement('span');
                const code = document.createElement('span');
                const name = document.createElement('span');
                code.className = 's-code';
                code.textContent = item.code;
                name.className = 's-name';
                name.textContent = item.name || '';
                texts.append(code, document.createElement('br'), name);

                button.append(thumb, texts);
                // mousedown chạy trước blur của ô nhập nên gợi ý không bị đóng mất.
                button.addEventListener('mousedown', event => {
                    event.preventDefault();
                    choose(item.code);
                });
                box.appendChild(button);
            });

            box.style.display = 'block';
        }

        function moveActive(step) {
            const buttons = [...box.querySelectorAll('.suggestion')];
            if (buttons.length === 0) return;

            buttons.forEach(button => button.classList.remove('is-active'));
            activeIndex = (activeIndex + step + buttons.length) % buttons.length;
            buttons[activeIndex].classList.add('is-active');
            buttons[activeIndex].scrollIntoView({ block: 'nearest' });
        }

        input.addEventListener('input', () => { renderSuggestions(); renderPreview(); });
        input.addEventListener('focus', renderSuggestions);
        input.addEventListener('blur', () => setTimeout(closeSuggestions, 120));

        input.addEventListener('keydown', event => {
            if (box.style.display !== 'block') return;

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                moveActive(event.key === 'ArrowDown' ? 1 : -1);
                return;
            }
            if (event.key === 'Enter' && activeIndex >= 0) {
                event.preventDefault();
                box.querySelectorAll('.suggestion')[activeIndex].dispatchEvent(new Event('mousedown'));
                return;
            }
            if (event.key === 'Escape') {
                closeSuggestions();
            }
        });

        pThumb.addEventListener('click', () => {
            if (pImage.style.display === 'none' || !pImage.src) return;
            lightboxImage.src = pImage.src;
            lightbox.classList.add('is-open');
        });
        lightbox.addEventListener('click', () => lightbox.classList.remove('is-open'));
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') lightbox.classList.remove('is-open');
        });

        // Mở form sửa (hoặc quay lại sau lỗi) thì hiện luôn ảnh của mã đang có.
        renderPreview();
    })();
</script>
</body>
</html>
