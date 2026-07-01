<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lí đơn hàng</title>
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
        .nav {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .nav a,
        .logout {
            background: #fff;
            border: 1px solid #e8b8c8;
            border-radius: 999px;
            color: #8b2f4d;
            cursor: pointer;
            font-weight: 700;
            padding: 10px 16px;
        }
        .nav a.active {
            background: #be476f;
            color: #fff;
        }
        .hero {
            background:
                linear-gradient(115deg, rgba(255, 255, 255, .93), rgba(255, 232, 241, .8)),
                url("https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=1600&q=80");
            background-position: center;
            background-size: cover;
            border-bottom: 1px solid #f5d9e2;
            min-height: 250px;
            padding: 48px clamp(18px, 5vw, 56px);
        }
        .hero h1 {
            color: #6f253f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(34px, 5vw, 58px);
            line-height: 1.04;
            margin: 0 0 14px;
            max-width: 760px;
        }
        .hero p {
            color: #704252;
            font-size: 17px;
            line-height: 1.6;
            margin: 0;
            max-width: 720px;
        }
        .content { padding: 28px clamp(18px, 5vw, 56px) 56px; }
        .toolbar {
            align-items: end;
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr auto;
            margin-bottom: 18px;
        }
        .search {
            display: grid;
            gap: 8px;
            max-width: 560px;
        }
        label {
            color: #7a344c;
            font-size: 13px;
            font-weight: 700;
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
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
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
        .status {
            background: #fff;
            border: 1px solid #f0c7d3;
            border-left: 5px solid #c9577d;
            border-radius: 8px;
            color: #693143;
            margin-bottom: 18px;
            padding: 12px 14px;
        }
        .table-shell {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
            overflow-x: auto;
        }
        table {
            border-collapse: collapse;
            min-width: 1120px;
            width: 100%;
        }
        th, td {
            border-bottom: 1px solid #f7e3e9;
            padding: 15px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background: #fff0f4;
            color: #81304c;
            font-size: 13px;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        th a {
            align-items: center;
            display: inline-flex;
            gap: 6px;
        }
        tbody tr:hover { background: #fff9fb; }
        .code {
            color: #a13b60;
            font-weight: 800;
        }
        .product-codes {
            display: grid;
            gap: 6px;
        }
        .product-code-line {
            line-height: 1.45;
        }
        .size-qty {
            color: #8b6672;
            font-size: 13px;
            font-weight: 600;
        }
        .name {
            color: #3f2730;
            font-weight: 800;
        }
        .muted {
            color: #8b6672;
            font-size: 13px;
        }
        .status-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 13px;
            font-weight: 900;
            padding: 7px 11px;
        }
        .status-form { margin: 0; }
        .status-select {
            border: 1px solid #f0d3dc;
            border-radius: 999px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 900;
            max-width: 180px;
            min-height: 40px;
            padding: 8px 10px;
        }
        .status-select:focus {
            border-color: #c9577d;
            box-shadow: 0 0 0 3px rgba(201, 87, 125, .16);
            outline: none;
        }
        .status-chua_cho_size { background: #f3eef1; color: #6b5560; }
        .status-da_in_file { background: #eaf2fd; color: #2f5fa6; }
        .status-da_in_giay { background: #eef0fb; color: #45489c; }
        .status-dang_soan { background: #f6ecfb; color: #7d3aa3; }
        .status-da_soan_xong { background: #e8f6f4; color: #1f7d6e; }
        .status-da_gui { background: #fff7d6; color: #8a5a00; }
        .status-da_tra_ve { background: #ffeede; color: #a85a18; }
        .status-thanh_cong { background: #e8f7ef; color: #247857; }
        .pay-coc { background: #f3eef1; color: #6b5560; }
        .pay-thanh_toan_1 { background: #eaf2fd; color: #2f5fa6; }
        .pay-thanh_toan_2 { background: #f6ecfb; color: #7d3aa3; }
        .pay-con_lai { background: #fff7d6; color: #8a5a00; }
        .row-actions {
            display: flex;
            gap: 8px;
        }
        .link-action,
        .danger {
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 800;
            padding: 8px 10px;
        }
        .link-action {
            background: #fff4f7;
            border: 1px solid #f1cbd7;
            color: #8b2f4d;
        }
        .danger {
            background: #fff;
            border: 1px solid #f0b7c1;
            color: #b4233f;
        }
        .empty {
            color: #8b6672;
            padding: 36px;
            text-align: center;
        }
        .pagination {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
            margin-top: 18px;
        }
        .pages {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .page-link,
        .page-current {
            border-radius: 8px;
            display: inline-flex;
            font-weight: 800;
            min-width: 40px;
            padding: 10px 12px;
            place-content: center;
        }
        .page-link {
            background: #fff;
            border: 1px solid #f0d3dc;
            color: #8b2f4d;
        }
        .page-current {
            background: #be476f;
            color: #fff;
        }
        @media (max-width: 760px) {
            .topbar,
            .toolbar {
                align-items: stretch;
                grid-template-columns: 1fr;
            }
            .topbar { flex-direction: column; }
            .nav, .actions { width: 100%; }
            .nav a, .logout, .button { justify-content: center; width: 100%; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('orders.index') }}">Tiba Boutique</a>
        <div class="nav">
            <a href="{{ route('products.index') }}">Kho hàng</a>
            <a class="active" href="{{ route('orders.index') }}">Đơn hàng</a>
            <a href="{{ route('stock-import-histories.index') }}">Lịch sử nhập</a>
            @if (auth()->user()?->isAdmin())
                <a href="{{ route('users.index') }}">Người dùng</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout" type="submit">Đăng xuất</button>
            </form>
        </div>
    </header>

    <section class="hero">
        <h1>Quản lí lên đơn hàng</h1>
        <p>Theo dõi người chốt, lịch lấy - diễn - trả và mã hàng từ kho để xử lý từng đơn thật gọn gàng.</p>
    </section>

    <main class="content">
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <div class="toolbar">
            <form class="search" method="GET" action="{{ route('orders.index') }}">
                <label for="q">Tìm theo người chốt, tên đơn, mã hàng hoặc tên hàng</label>
                <input id="q" name="q" type="search" value="{{ $query }}" placeholder="VD: Linh, đơn chụp lookbook, VAY001...">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
            </form>

            <div class="actions">
                <a class="button secondary" href="{{ route('orders.index') }}">Làm mới</a>
                <a class="button" href="{{ route('orders.create') }}">+ Tạo đơn hàng</a>
            </div>
        </div>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th><a href="{{ route('orders.index', array_merge(request()->except('page'), ['sort' => 'closer', 'direction' => $sort === 'closer' && $direction === 'asc' ? 'desc' : 'asc'])) }}">Người chốt {{ $sort === 'closer' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}</a></th>
                        <th><a href="{{ route('orders.index', array_merge(request()->except('page'), ['sort' => 'pickup_date', 'direction' => $sort === 'pickup_date' && $direction === 'asc' ? 'desc' : 'asc'])) }}">Ngày lấy {{ $sort === 'pickup_date' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}</a></th>
                        <th><a href="{{ route('orders.index', array_merge(request()->except('page'), ['sort' => 'event_date', 'direction' => $sort === 'event_date' && $direction === 'asc' ? 'desc' : 'asc'])) }}">Ngày diễn {{ $sort === 'event_date' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}</a></th>
                        <th><a href="{{ route('orders.index', array_merge(request()->except('page'), ['sort' => 'return_date', 'direction' => $sort === 'return_date' && $direction === 'asc' ? 'desc' : 'asc'])) }}">Ngày trả {{ $sort === 'return_date' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}</a></th>
                        <th><a href="{{ route('orders.index', array_merge(request()->except('page'), ['sort' => 'order_name', 'direction' => $sort === 'order_name' && $direction === 'asc' ? 'desc' : 'asc'])) }}">Tên đơn {{ $sort === 'order_name' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}</a></th>
                        <th><a href="{{ route('orders.index', array_merge(request()->except('page'), ['sort' => 'product_code', 'direction' => $sort === 'product_code' && $direction === 'asc' ? 'desc' : 'asc'])) }}">Mã hàng {{ $sort === 'product_code' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}</a></th>
                        <th><a href="{{ route('orders.index', array_merge(request()->except('page'), ['sort' => 'quantity', 'direction' => $sort === 'quantity' && $direction === 'asc' ? 'desc' : 'asc'])) }}">Số lượng {{ $sort === 'quantity' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}</a></th>
                        <th><a href="{{ route('orders.index', array_merge(request()->except('page'), ['sort' => 'status', 'direction' => $sort === 'status' && $direction === 'asc' ? 'desc' : 'asc'])) }}">Trạng thái {{ $sort === 'status' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}</a></th>
                        <th>Thanh toán</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="name">{{ $order->closer_name }}</td>
                            <td>{{ $order->pickup_date?->format('d/m/Y') }}</td>
                            <td>{{ $order->event_date?->format('d/m/Y') }}</td>
                            <td>{{ $order->return_date?->format('d/m/Y') }}</td>
                            <td>
                                <div class="name">{{ $order->order_name }}</div>
                                <div class="muted">Tạo: {{ $order->created_at?->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                <div class="product-codes">
                                    @forelse ($order->items->groupBy(fn ($item) => $item->product->code) as $code => $items)
                                        <div class="product-code-line">
                                            <span class="code">{{ $code }}</span>
                                            <span class="size-qty">
                                                -
                                                @foreach ($items->groupBy(fn ($item) => $item->product->size) as $size => $sizeItems)
                                                    Size {{ $size }}: {{ number_format($sizeItems->sum('quantity')) }}@if (! $loop->last), @endif
                                                @endforeach
                                            </span>
                                        </div>
                                    @empty
                                        <div class="product-code-line">
                                            <span class="code">{{ $order->product->code }}</span>
                                            <span class="size-qty">- Size {{ $order->product->size }}: {{ number_format($order->quantity) }}</span>
                                        </div>
                                    @endforelse
                                </div>
                            </td>
                            <td>{{ number_format($order->items->sum('quantity') ?: $order->quantity) }}</td>
                            <td>
                                @php
                                    $checkItems = $order->items->map(fn ($item) => [
                                        'id' => $item->id,
                                        'code' => $item->product?->code ?? 'N/A',
                                        'size' => $item->product?->size ?? 'N/A',
                                        'name' => $item->product?->name ?? '',
                                        'quantity' => (int) $item->quantity,
                                        'returned' => $item->returned_quantity ?? (int) $item->quantity,
                                    ])->values();
                                @endphp
                                <form method="POST" action="{{ route('orders.status', $order) }}" class="status-form">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="status-select status-{{ $order->status }}"
                                        data-current="{{ $order->status }}"
                                        data-order-name="{{ $order->order_name }}"
                                        data-check-url="{{ route('orders.status', $order) }}"
                                        data-check-note="{{ $order->check_note }}"
                                        data-items="{{ json_encode($checkItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                                        aria-label="Cập nhật trạng thái đơn">
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('orders.payment-status', $order) }}" class="status-form">
                                    @csrf
                                    @method('PATCH')
                                    <select name="payment_status" class="status-select pay-{{ $order->payment_status }}" onchange="this.form.submit()" aria-label="Cập nhật trạng thái thanh toán">
                                        @foreach ($paymentStatuses as $value => $label)
                                            <option value="{{ $value }}" @selected($order->payment_status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a class="link-action" href="{{ route('orders.show', $order) }}">Xem</a>
                                    <a class="link-action" href="{{ route('orders.edit', $order) }}">Sửa</a>
                                    <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('Xóa đơn hàng này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger" type="submit">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty" colspan="10">Chưa có đơn hàng phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <nav class="pagination" aria-label="Phân trang">
                <div class="muted">
                    Hiển thị {{ $orders->firstItem() }}-{{ $orders->lastItem() }} trong {{ $orders->total() }} đơn hàng
                </div>
                <div class="pages">
                    @if ($orders->onFirstPage())
                        <span class="page-link">Trước</span>
                    @else
                        <a class="page-link" href="{{ $orders->previousPageUrl() }}">Trước</a>
                    @endif

                    @for ($page = 1; $page <= $orders->lastPage(); $page++)
                        @if ($page === $orders->currentPage())
                            <span class="page-current">{{ $page }}</span>
                        @else
                            <a class="page-link" href="{{ $orders->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor

                    @if ($orders->hasMorePages())
                        <a class="page-link" href="{{ $orders->nextPageUrl() }}">Sau</a>
                    @else
                        <span class="page-link">Sau</span>
                    @endif
                </div>
            </nav>
        @endif
    </main>
    @include('orders.check-modal')
    @include('orders.reminders-popup')
</body>
</html>


