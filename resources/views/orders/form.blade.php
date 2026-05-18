<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'create' ? 'Tạo đơn hàng' : 'Sửa đơn hàng' }}</title>
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
        .content { padding: 36px clamp(18px, 5vw, 56px) 56px; }
        .heading { margin-bottom: 22px; }
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
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            max-width: 980px;
            padding: 24px;
        }
        .field {
            display: grid;
            gap: 7px;
        }
        .field.full { grid-column: 1 / -1; }
        label {
            color: #7a344c;
            font-size: 13px;
            font-weight: 800;
        }
        input,
        select {
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 8px;
            color: #3f2730;
            font-size: 15px;
            min-height: 44px;
            padding: 10px 13px;
            width: 100%;
        }
        input:focus,
        select:focus {
            border-color: #c9577d;
            box-shadow: 0 0 0 3px rgba(201, 87, 125, .16);
            outline: none;
        }
        .error {
            color: #b4233f;
            font-size: 13px;
            font-weight: 700;
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
        @media (max-width: 760px) {
            .topbar,
            .form-shell {
                align-items: stretch;
                grid-template-columns: 1fr;
            }
            .topbar { flex-direction: column; }
            .actions { justify-content: stretch; }
            .button { justify-content: center; width: 100%; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('orders.index') }}">Tiba Boutique</a>
        <a class="button secondary" href="{{ route('orders.index') }}">Về danh sách</a>
    </header>

    <main class="content">
        <div class="heading">
            <h1>{{ $mode === 'create' ? 'Tạo đơn hàng mới' : 'Sửa thông tin đơn hàng' }}</h1>
            <p>Chọn mã hàng từ kho, cập nhật lịch lấy, lịch diễn, lịch trả và trạng thái xử lý đơn.</p>
        </div>

        <form class="form-shell" method="POST" action="{{ $mode === 'create' ? route('orders.store') : route('orders.update', $order) }}">
            @csrf
            @if ($mode === 'edit')
                @method('PUT')
            @endif

            <div class="field">
                <label for="closer_name">Người chốt</label>
                <input id="closer_name" name="closer_name" type="text" value="{{ old('closer_name', $order->closer_name) }}" required>
                @error('closer_name') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="order_name">Tên đơn</label>
                <input id="order_name" name="order_name" type="text" value="{{ old('order_name', $order->order_name) }}" required>
                @error('order_name') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="pickup_date">Ngày lấy</label>
                <input id="pickup_date" name="pickup_date" type="date" value="{{ old('pickup_date', $order->pickup_date?->format('Y-m-d')) }}" required>
                @error('pickup_date') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="event_date">Ngày diễn</label>
                <input id="event_date" name="event_date" type="date" value="{{ old('event_date', $order->event_date?->format('Y-m-d')) }}" required>
                @error('event_date') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="return_date">Ngày trả</label>
                <input id="return_date" name="return_date" type="date" value="{{ old('return_date', $order->return_date?->format('Y-m-d')) }}" required>
                @error('return_date') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="quantity">Số lượng</label>
                <input id="quantity" name="quantity" type="number" min="1" step="1" value="{{ old('quantity', $order->quantity ?? 1) }}" required>
                @error('quantity') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status" required>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $order->status ?? 'len_don') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('status') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field full">
                <label for="product_id">Mã hàng trong kho</label>
                <select id="product_id" name="product_id" required>
                    <option value="">Chọn mã hàng</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((int) old('product_id', $order->product_id) === $product->id)>
                            {{ $product->code }} - {{ $product->name }} | Tồn: {{ $product->stock_quantity }} | Vải: {{ $product->fabric }} | Size: {{ $product->size }}
                        </option>
                    @endforeach
                </select>
                @error('product_id') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="actions">
                <a class="button secondary" href="{{ route('orders.index') }}">Hủy</a>
                <button class="button" type="submit">{{ $mode === 'create' ? 'Tạo đơn hàng' : 'Lưu thay đổi' }}</button>
            </div>
        </form>
    </main>
    @include('orders.reminders-popup')
</body>
</html>


