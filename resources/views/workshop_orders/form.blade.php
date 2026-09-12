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
        .preview {
            align-items: center;
            background: #fff4f7;
            border: 1px solid #f2d3dc;
            border-radius: 8px;
            color: #704252;
            display: flex;
            font-size: 13px;
            gap: 12px;
            grid-column: 1 / -1;
            padding: 12px;
        }
        .preview img {
            border: 1px solid #f1cbd7;
            border-radius: 8px;
            height: 72px;
            object-fit: cover;
            width: 72px;
        }
        .preview .code { color: #a13b60; font-weight: 900; }
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
            <input id="product_code" name="product_code" list="product_codes" autocomplete="off" required
                value="{{ old('product_code', $workshopOrder->product_code) }}" placeholder="Gõ hoặc chọn mã trong kho">
            <datalist id="product_codes">
                @foreach ($productCodes as $item)
                    <option value="{{ $item['code'] }}">{{ $item['name'] }}</option>
                @endforeach
            </datalist>
            <span class="hint">Chỉ nhận mã đã có trong kho.</span>
            @error('product_code') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="size_note">Size</label>
            <input id="size_note" name="size_note" type="text"
                value="{{ old('size_note', $workshopOrder->size_note) }}" placeholder="VD: 5s 5m 2L 3xl">
            <span class="hint">Ghi tự do số lượng theo từng size.</span>
            @error('size_note') <div class="error">{{ $message }}</div> @enderror
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

        @if ($mode === 'edit' && $workshopOrder->product)
            <div class="preview">
                @if ($workshopOrder->product->image_path)
                    <img src="{{ asset('storage/' . $workshopOrder->product->image_path) }}" alt="{{ $workshopOrder->product_code }}">
                @endif
                <div>
                    <div class="code">{{ $workshopOrder->product_code }}</div>
                    <div>{{ $workshopOrder->product->name }}</div>
                </div>
            </div>
        @endif

        <div class="actions">
            <a class="button secondary" href="{{ route('workshop-orders.index') }}">Hủy</a>
            <button class="button" type="submit">{{ $mode === 'create' ? 'Thêm dòng' : 'Lưu thay đổi' }}</button>
        </div>
    </form>
</main>
</div>
</body>
</html>
