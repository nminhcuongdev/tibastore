<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
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
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr;
            margin-bottom: 18px;
        }
        .toolbar-head {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: space-between;
        }
        .filter-toggle {
            align-items: center;
            display: inline-flex;
            gap: 8px;
        }
        .filter-toggle__caret { display: inline-block; transition: transform .18s ease; }
        .filter-toggle[aria-expanded="true"] .filter-toggle__caret { transform: rotate(90deg); }
        .filter-toggle__badge {
            background: #be476f;
            border-radius: 999px;
            color: #fff;
            font-size: 11px;
            font-weight: 900;
            padding: 3px 9px;
        }
        /* Mac dinh dong; mo bang nut toggle hoac khi dang co bo loc. */
        .search {
            display: none;
            gap: 12px;
            width: 100%;
        }
        .search.is-open { display: grid; }
        .filter-grid {
            align-items: end;
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
        .filter-field {
            display: grid;
            gap: 8px;
        }
        .filter-field.wide {
            grid-column: 1 / -1;
        }
        .filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .status-checks {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        /* Moi trang thai mot the bam duoc, to dung mau trang thai khi da chon. */
        .status-check {
            align-items: center;
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 999px;
            color: #8b2f4d;
            cursor: pointer;
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            gap: 7px;
            padding: 9px 14px;
            user-select: none;
        }
        .status-check:hover { border-color: #c9577d; }
        .status-check input { accent-color: #be476f; cursor: pointer; margin: 0; }
        .status-check:has(input:checked) { border-color: transparent; color: #fff; }
        .status-check:has(input:focus-visible) { box-shadow: 0 0 0 3px rgba(201, 87, 125, .22); }
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
        select:focus {
            border-color: #c9577d;
            box-shadow: 0 0 0 3px rgba(201, 87, 125, .16);
            outline: none;
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
            border-left: 5px solid #2f9e6f;
            border-radius: 8px;
            color: #236c4f;
            margin-bottom: 18px;
            padding: 12px 14px;
        }
        .status.is-error {
            border-left-color: #b4233f;
            color: #b4233f;
        }
        .table-shell {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
            max-height: 74vh;
            overflow: auto;
        }
        table {
            border-collapse: collapse;
            min-width: 1440px;
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
        /* Ghim dong tieu de khi cuon trong khung bang. */
        thead th {
            position: sticky;
            top: 0;
            z-index: 3;
        }
        /* border-collapse an vien duoi cua o ghim, ve lai bang box-shadow. */
        thead th::after {
            box-shadow: inset 0 -1px 0 #f0d3dc;
            content: '';
            inset: 0;
            pointer-events: none;
            position: absolute;
        }
        th a {
            align-items: center;
            display: inline-flex;
            gap: 6px;
        }
        tbody tr:hover td { box-shadow: inset 0 0 0 100vh rgba(63, 39, 48, .05); }
        /* Nen dong dong bo voi mau trang thai o cot "Trang thai". */
        .row-chua_cho_size { background: #f3eef1; }
        .row-da_in_file { background: #eaf2fd; }
        .row-da_in_giay { background: #eef0fb; }
        .row-dang_soan { background: #f6ecfb; }
        .row-da_soan_xong { background: #e8f6f4; }
        .row-da_gui { background: #fff7d6; }
        .row-da_tra_ve { background: #ffeede; }
        .row-thanh_cong { background: #e8f7ef; }
        .row-thanh_cong_thieu { background: #fdecec; }
        .code {
            color: #a13b60;
            font-weight: 800;
        }
        .view-codes {
            min-height: 36px;
            padding: 8px 16px;
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
            border: 1px solid rgba(0, 0, 0, .14);
            border-radius: 999px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 900;
            min-height: 42px;
            min-width: 215px;
            padding: 8px 14px;
            width: 100%;
        }
        /* Danh sach xo xuong giu nen sang de van doc duoc tren nen dam. */
        .status-select option { background: #fff; color: #3f2730; }
        .status-select:focus {
            border-color: #c9577d;
            box-shadow: 0 0 0 3px rgba(201, 87, 125, .16);
            outline: none;
        }
        .status-chua_cho_size { background: #6b5560; color: #fff; }
        .status-da_in_file { background: #2f5fa6; color: #fff; }
        .status-da_in_giay { background: #45489c; color: #fff; }
        .status-dang_soan { background: #7d3aa3; color: #fff; }
        .status-da_soan_xong { background: #1f7d6e; color: #fff; }
        .status-da_gui { background: #8a5a00; color: #fff; }
        .status-da_tra_ve { background: #a85a18; color: #fff; }
        .status-thanh_cong { background: #247857; color: #fff; }
        .status-thanh_cong_thieu { background: #b4233f; color: #fff; }
        /* Chua chon thi giu nen trang; chi to mau khi da tick. */
        .status-check:not(:has(input:checked)) { background: #fff; color: #8b2f4d; }
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
            .toolbar,
            .filter-grid {
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
<div class="layout">
@include('partials.sidebar', ['active' => 'orders'])
<div class="page">

    <section class="hero">
        <h1>Quản lí lên đơn hàng</h1>
        <p>Theo dõi người chốt, lịch lấy - diễn - trả và mã hàng từ kho để xử lý từng đơn thật gọn gàng.</p>
    </section>

    <main class="content">
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="status is-error">
                @foreach ($errors->all() as $message)
                    <div>{{ $message }}</div>
                @endforeach
            </div>
        @endif
        @if (session('error'))
            <div class="status is-error">{{ session('error') }}</div>
        @endif

        @php
            // Co bo loc nao dang bat thi mo san, de khong bi loc ma khong biet vi sao.
            $hasActiveFilters = $query !== ''
                || $selectedStatuses !== []
                || collect($filters)->contains(fn ($value) => $value !== null && $value !== '');
        @endphp
        <div class="toolbar">
            <div class="toolbar-head">
                <button type="button" class="button secondary filter-toggle" data-filter-toggle
                    aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}" aria-controls="filter-panel">
                    <span class="filter-toggle__caret" aria-hidden="true">▸</span>
                    Bộ lọc &amp; tìm kiếm
                    @if ($hasActiveFilters)
                        <span class="filter-toggle__badge">đang lọc</span>
                    @endif
                </button>
                <a class="button" href="{{ route('orders.create') }}">+ Tạo đơn hàng</a>
            </div>
            <form class="search {{ $hasActiveFilters ? 'is-open' : '' }}" id="filter-panel" method="GET" action="{{ route('orders.index') }}">
                <div class="filter-grid">
                    <div class="filter-field wide">
                        <label for="q">Tìm theo người chốt, tên đơn, mã hàng hoặc tên hàng</label>
                        <input id="q" name="q" type="search" value="{{ $query }}" placeholder="VD: Linh, đơn chụp lookbook, VAY001...">
                    </div>
                    <div class="filter-field">
                        <label for="order_name">Tên đơn (tên người đặt)</label>
                        <input id="order_name" name="order_name" type="search" value="{{ $filters['order_name'] }}" placeholder="Dán tên người đặt...">
                    </div>
                    <div class="filter-field">
                        <label for="closer">Người chốt</label>
                        <select id="closer" name="closer">
                            <option value="">Tất cả người chốt</option>
                            @foreach ($closers as $name)
                                <option value="{{ $name }}" @selected($filters['closer'] === $name)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="source">Nguồn hàng</label>
                        <select id="source" name="source">
                            <option value="">Tất cả nguồn</option>
                            @foreach ($sources as $value => $label)
                                <option value="{{ $value }}" @selected($filters['source'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field wide">
                        <label>Trạng thái <span class="muted">(chọn nhiều được; bỏ trống = tất cả)</span></label>
                        <div class="status-checks">
                            @foreach ($statuses as $value => $label)
                                <label class="status-check status-{{ $value }}">
                                    <input type="checkbox" name="status[]" value="{{ $value }}"
                                        @checked(in_array($value, $selectedStatuses, true))>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="filter-field">
                        <label for="pickup_from">Ngày lấy từ</label>
                        <input id="pickup_from" name="pickup_from" type="date" value="{{ $filters['pickup_from'] }}">
                    </div>
                    <div class="filter-field">
                        <label for="pickup_to">Ngày lấy đến</label>
                        <input id="pickup_to" name="pickup_to" type="date" value="{{ $filters['pickup_to'] }}">
                    </div>
                    <div class="filter-field">
                        <label for="event_from">Ngày diễn từ</label>
                        <input id="event_from" name="event_from" type="date" value="{{ $filters['event_from'] }}">
                    </div>
                    <div class="filter-field">
                        <label for="event_to">Ngày diễn đến</label>
                        <input id="event_to" name="event_to" type="date" value="{{ $filters['event_to'] }}">
                    </div>
                    <div class="filter-field">
                        <label for="return_from">Ngày trả từ</label>
                        <input id="return_from" name="return_from" type="date" value="{{ $filters['return_from'] }}">
                    </div>
                    <div class="filter-field">
                        <label for="return_to">Ngày trả đến</label>
                        <input id="return_to" name="return_to" type="date" value="{{ $filters['return_to'] }}">
                    </div>
                </div>
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <div class="filter-actions">
                    <button type="submit" class="button">Lọc</button>
                    <a class="button secondary" href="{{ route('orders.index') }}">Xóa lọc</a>
                </div>
            </form>
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
                        <th>Tổng đơn</th>
                        <th>Tiền ship</th>
                        <th>Thanh toán lần 1</th>
                        <th>Thanh toán lần 2</th>
                        <th>Còn lại</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="row-{{ $order->statusColorKey() }}">
                            <td class="name">{{ $order->closer_name }}</td>
                            <td>{{ $order->pickup_date?->format('d/m/Y') }}</td>
                            <td>{{ $order->event_date?->format('d/m/Y') }}</td>
                            <td>{{ $order->return_date?->format('d/m/Y') }}</td>
                            <td>
                                <div class="name">{{ $order->order_name }}</div>
                                <div class="muted">Tạo: {{ $order->created_at?->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                @php
                                    // Gộp theo mã + size: cùng mã-size ở nhiều dòng giá khác nhau
                                    // vẫn hiện một dòng với tổng số lượng.
                                    $codeRows = $order->items->isNotEmpty()
                                        ? $order->items
                                            ->groupBy(fn ($item) => ($item->product?->code ?? 'N/A') . '|' . $item->displaySize())
                                            ->map(fn ($group) => [
                                                'code' => $group->first()->product?->code ?? 'N/A',
                                                'name' => $group->first()->product?->name ?? '',
                                                'size' => $group->first()->displaySize(),
                                                'quantity' => (int) $group->sum('quantity'),
                                                'image' => $group->first()->product?->image_path
                                                    ? asset('storage/' . $group->first()->product->image_path)
                                                    : null,
                                            ])
                                            ->values()
                                        : collect([[
                                            'code' => $order->product?->code ?? 'N/A',
                                            'name' => $order->product?->name ?? '',
                                            'size' => $order->product?->size ?? 'N/A',
                                            'quantity' => (int) $order->quantity,
                                            'image' => $order->product?->image_path
                                                ? asset('storage/' . $order->product->image_path)
                                                : null,
                                        ]]);
                                @endphp
                                <button type="button" class="button secondary view-codes"
                                    data-codes="{{ json_encode($codeRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                                    data-order-name="{{ $order->order_name }}">Xem</button>
                            </td>
                            <td>{{ number_format($order->items->sum('quantity') ?: $order->quantity) }}</td>
                            <td>
                                @php
                                    $checkItems = $order->items->map(fn ($item) => [
                                        'id' => $item->id,
                                        'code' => $item->product?->code ?? 'N/A',
                                        'size' => $item->displaySize(),
                                        'name' => $item->product?->name ?? '',
                                        'quantity' => (int) $item->quantity,
                                        'returned' => $item->returned_quantity ?? (int) $item->quantity,
                                        // Cho modal xac nhan doi trang thai tinh truoc thay doi ton.
                                        'size_pending' => (bool) $item->size_pending,
                                        'stock' => (int) ($item->product?->stock_quantity ?? 0),
                                    ])->values();
                                @endphp
                                <form method="POST" action="{{ route('orders.status', $order) }}" class="status-form">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="status-select status-{{ $order->statusColorKey() }}"
                                        data-current="{{ $order->status }}"
                                        data-currently-out="{{ $order->stock_decreased_at && ! $order->stock_returned_at ? '1' : '0' }}"
                                        data-order-name="{{ $order->order_name }}"
                                        data-check-url="{{ route('orders.status', $order) }}"
                                        data-check-note="{{ $order->check_note }}"
                                        data-compensation="{{ $order->compensation_amount }}"
                                        data-items="{{ json_encode($checkItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                                        aria-label="Cập nhật trạng thái đơn">
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td>{{ number_format($order->total_with_compensation) }}</td>
                            <td>{{ number_format($order->shipping_fee) }}</td>
                            <td>{{ number_format($order->payment_1) }}</td>
                            <td>{{ number_format($order->payment_2) }}</td>
                            <td>{{ number_format($order->remaining) }}</td>
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
                            <td class="empty" colspan="14">Chưa có đơn hàng phù hợp.</td>
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
    <script>
        (function () {
            const toggle = document.querySelector('[data-filter-toggle]');
            const panel = document.getElementById('filter-panel');
            if (!toggle || !panel) return;

            toggle.addEventListener('click', function () {
                const open = panel.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        })();
    </script>
    @include('orders.codes-modal')
    @include('orders.status-confirm-modal')
    @include('orders.check-modal')
    @include('orders.reminders-popup')
    </div>
</div>
</body>
</html>


