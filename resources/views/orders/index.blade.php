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
            overflow-x: auto;
        }
        table {
            border-collapse: collapse;
            /* Them cot Mien va Nha xe nen noi rong de chu khong bi bop lai. */
            min-width: 1720px;
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
        /* Moi mien mot mau nhat de liec la phan biet duoc vung khach. */
        .region-badge {
            border-radius: 999px;
            display: inline-block;
            font-size: 12px;
            font-weight: 900;
            padding: 3px 9px;
            white-space: nowrap;
        }
        .region-tinh_mb { background: #e4eefc; color: #2f5fa6; }
        .region-tinh_mt { background: #fdf0da; color: #8a5a00; }
        .region-tinh_mn { background: #e6f6ee; color: #247857; }
        .region-ha_noi { background: #f6e8fb; color: #7d3aa3; }
        .region-badge:hover, .carrier:hover { box-shadow: 0 0 0 2px rgba(201, 87, 125, .35); }
        .carrier {
            border-radius: 5px;
            display: inline-block;
            font-weight: 700;
            padding: 2px 4px;
        }
        .code-buttons { display: flex; flex-wrap: wrap; gap: 5px; }
        .codes-toggle,
        .view-codes {
            min-height: 32px;
            padding: 5px 11px;
            white-space: nowrap;
        }
        .codes-caret {
            display: inline-block;
            transition: transform .15s ease;
        }
        [aria-expanded="true"] > .codes-caret { transform: rotate(90deg); }
        /* Xem nhanh vai ma ngay tren cot, khoi phai so ra moi biet don co gi. */
        .code-peek {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-bottom: 5px;
        }
        .code-chip {
            background: rgba(255, 255, 255, .75);
            border: 1px solid #ebc5d2;
            border-radius: 999px;
            color: #a13b60;
            display: inline-flex;
            font-size: 11px;
            font-weight: 900;
            gap: 4px;
            padding: 2px 7px;
        }
        .code-chip__size { color: #8b6672; font-weight: 700; }
        .code-chip.is-more { color: #8b6672; }
        .codes-row > td { padding: 0 13px 12px; }
        .codes-panel {
            background: rgba(255, 255, 255, .78);
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            padding: 10px 12px;
        }
        .codes-panel__head {
            align-items: baseline;
            border-bottom: 1px dashed #f0d3dc;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 9px;
            padding-bottom: 7px;
        }
        .codes-panel__title { color: #6f253f; font-weight: 900; }
        .codes-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
        }
        .code-card {
            align-items: center;
            background: #fff;
            border: 1px solid #f2d3dc;
            border-radius: 8px;
            display: flex;
            gap: 8px;
            padding: 6px;
        }
        .code-thumb {
            align-items: center;
            background: #f9e5ec;
            border: 1px solid #f1cbd7;
            border-radius: 6px;
            color: #a64465;
            display: flex;
            flex: 0 0 auto;
            font-family: inherit;
            font-size: 8px;
            font-weight: 800;
            height: 48px;
            justify-content: center;
            overflow: hidden;
            padding: 0;
            text-align: center;
            width: 48px;
        }
        .code-thumb.has-image { cursor: zoom-in; }
        .code-thumb img { height: 100%; object-fit: cover; transition: transform .18s ease; width: 100%; }
        /* Hover phong to tai cho, bam mo anh lon - giong ben kho hang. */
        .code-thumb.has-image:hover,
        .code-thumb.has-image:focus {
            border-color: #c9577d;
            box-shadow: 0 10px 24px rgba(117, 44, 69, .18);
            outline: none;
            overflow: visible;
            z-index: 20;
        }
        .code-thumb.has-image:hover img,
        .code-thumb.has-image:focus img {
            border-radius: 6px;
            transform: scale(2.6);
        }
        .code-card__info { min-width: 0; }
        .code-card__code { color: #a13b60; font-weight: 900; }
        .code-card__name {
            color: #704252;
            font-size: 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .code-card__meta { display: flex; font-size: 12px; gap: 8px; }
        .code-card__note {
            color: #8a5a00;
            font-size: 11px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        /* Ghi chu dai thi cat bot, re chuot vao xem day du. */
        .note-cell { max-width: 240px; }
        .note-line {
            color: #704252;
            display: -webkit-box;
            font-size: 12px;
            line-height: 1.45;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }
        .note-line + .note-line { margin-top: 3px; }
        .note-tag {
            background: #fff0f4;
            border-radius: 4px;
            color: #a13b60;
            font-weight: 900;
            padding: 1px 5px;
        }
        .note-tag.is-check { background: #fdf0da; color: #8a5a00; }
        .code-card__size { color: #8b6672; font-weight: 700; }
        .code-card__qty { color: #3f2730; font-weight: 900; }
        .codes-zoom {
            align-items: center;
            background: rgba(63, 39, 48, .82);
            cursor: zoom-out;
            display: none;
            inset: 0;
            justify-content: center;
            padding: 24px;
            position: fixed;
            z-index: 1200;
        }
        .codes-zoom.is-open { display: flex; }
        .codes-zoom img {
            background: #fff;
            border: 8px solid #fff;
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
            max-height: min(86vh, 820px);
            max-width: min(90vw, 820px);
            object-fit: contain;
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
    @include('partials.compact')
</head>
<body>
<div class="layout">
@include('partials.sidebar', ['active' => 'orders'])
<div class="page">

<main class="content">
        <h1>Quản lí lên đơn hàng</h1>
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
        {{-- Cho thong bao cua lan doi trang thai bang AJAX. --}}
        <div data-flash-area></div>

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
                <button type="button" class="button secondary codes-toggle-all" data-codes-toggle-all
                    aria-expanded="false">
                    <span class="codes-caret" aria-hidden="true">▸</span>
                    <span data-codes-toggle-all-label>Sổ hết tất cả</span>
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
                        <label for="phone">Số điện thoại</label>
                        <input id="phone" name="phone" type="search" value="{{ $filters['phone'] }}" placeholder="VD: 0901234...">
                    </div>
                    <div class="filter-field">
                        <label for="carrier">Nhà xe</label>
                        <input id="carrier" name="carrier" type="search" value="{{ $filters['carrier'] }}" placeholder="VD: Hải Vân, Phương Trang...">
                    </div>
                    <div class="filter-field">
                        <label for="region">Miền</label>
                        <select id="region" name="region">
                            <option value="">Tất cả miền</option>
                            @foreach ($regions as $value => $label)
                                <option value="{{ $value }}" @selected($filters['region'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
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
                    <div class="filter-field">
                        <label for="remaining">Còn lại</label>
                        <select id="remaining" name="remaining">
                            <option value="">Tất cả</option>
                            <option value="nonzero" @selected($filters['remaining'] === 'nonzero')>Khác 0 (chưa tất toán)</option>
                            <option value="zero" @selected($filters['remaining'] === 'zero')>Bằng 0 (đã tất toán)</option>
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
                        <th><a href="{{ route('orders.index', array_merge(request()->except('page'), ['sort' => 'region', 'direction' => $sort === 'region' && $direction === 'asc' ? 'desc' : 'asc'])) }}">Miền {{ $sort === 'region' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}</a></th>
                        <th><a href="{{ route('orders.index', array_merge(request()->except('page'), ['sort' => 'carrier', 'direction' => $sort === 'carrier' && $direction === 'asc' ? 'desc' : 'asc'])) }}">Nhà xe {{ $sort === 'carrier' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}</a></th>
                        <th><a href="{{ route('orders.index', array_merge(request()->except('page'), ['sort' => 'product_code', 'direction' => $sort === 'product_code' && $direction === 'asc' ? 'desc' : 'asc'])) }}">Mã hàng {{ $sort === 'product_code' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}</a></th>
                        <th><a href="{{ route('orders.index', array_merge(request()->except('page'), ['sort' => 'quantity', 'direction' => $sort === 'quantity' && $direction === 'asc' ? 'desc' : 'asc'])) }}">Số lượng {{ $sort === 'quantity' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}</a></th>
                        <th><a href="{{ route('orders.index', array_merge(request()->except('page'), ['sort' => 'status', 'direction' => $sort === 'status' && $direction === 'asc' ? 'desc' : 'asc'])) }}">Trạng thái {{ $sort === 'status' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}</a></th>
                        <th>Tổng đơn</th>
                        <th>Tiền ship</th>
                        <th>Thanh toán lần 1</th>
                        <th>Thanh toán lần 2</th>
                        <th>Còn lại</th>
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php
                            $codeSummary = $order->codeSummary();
                            $codeTotal = $codeSummary->sum('quantity');
                        @endphp
                        <tr class="row-{{ $order->statusColorKey() }}" data-order-row="{{ $order->id }}">
                            <td class="name">{{ $order->closer_name }}</td>
                            <td>{{ $order->pickup_date?->format('d/m/Y') }}</td>
                            <td>{{ $order->event_date?->format('d/m/Y') }}</td>
                            <td>{{ $order->return_date?->format('d/m/Y') }}</td>
                            <td>
                                <div class="name">{{ $order->order_name }}</div>
                                <div class="muted">Tạo: {{ $order->created_at?->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                @if ($order->region)
                                    {{-- Bam vao la loc luon theo mien nay, giu nguyen cac loc khac. --}}
                                    <a class="region-badge region-{{ $order->region }}"
                                       href="{{ route('orders.index', array_merge(request()->except('page'), ['region' => $order->region])) }}"
                                       title="Lọc các đơn {{ $order->regionLabel() }}">{{ $order->regionLabel() }}</a>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($order->carrier)
                                    <a class="carrier"
                                       href="{{ route('orders.index', array_merge(request()->except('page'), ['carrier' => $order->carrier])) }}"
                                       title="Lọc các đơn đi nhà xe {{ $order->carrier }}">{{ $order->carrier }}</a>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($codeSummary->isEmpty())
                                    <span class="muted">—</span>
                                @else
                                    {{-- Xem nhanh ngay trong cot; bam de so het ma + anh o hang duoi. --}}
                                    <div class="code-peek">
                                        @foreach ($codeSummary->take(2) as $code)
                                            <span class="code-chip">{{ $code['code'] }}<span class="code-chip__size">{{ $code['size'] }}</span></span>
                                        @endforeach
                                        @if ($codeSummary->count() > 2)
                                            <span class="code-chip is-more">+{{ $codeSummary->count() - 2 }}</span>
                                        @endif
                                    </div>
                                    <div class="code-buttons">
                                        {{-- So ra tai cho (giong ben quan ly kho) hoac mo modal rieng. --}}
                                        <button type="button" class="button secondary codes-toggle" data-codes-toggle="{{ $order->id }}"
                                            aria-expanded="false" aria-controls="codes-panel-{{ $order->id }}">
                                            <span class="codes-caret" aria-hidden="true">▸</span>
                                            Sổ {{ $codeSummary->count() }} mã
                                        </button>
                                        <button type="button" class="button secondary view-codes"
                                            data-order-id="{{ $order->id }}"
                                            data-order-name="{{ $order->order_name }}">Xem</button>
                                    </div>
                                @endif
                            </td>
                            <td>{{ number_format($order->items->sum('quantity') ?: $order->quantity) }}</td>
                            <td>
                                <form method="POST" action="{{ route('orders.status', $order) }}" class="status-form">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="status-select status-{{ $order->statusColorKey() }}"
                                        data-current="{{ $order->status }}"
                                        data-currently-out="{{ $order->stock_decreased_at && ! $order->stock_returned_at ? '1' : '0' }}"
                                        data-order-name="{{ $order->order_name }}"
                                        data-check-url="{{ route('orders.status', $order) }}"
                                        data-order-id="{{ $order->id }}"
                                        aria-label="Cập nhật trạng thái đơn">
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td data-cell="total">{{ number_format($order->total_with_compensation) }}</td>
                            <td>{{ number_format($order->shipping_fee) }}</td>
                            <td>{{ number_format($order->payment_1) }}</td>
                            <td>{{ number_format($order->payment_2) }}</td>
                            <td data-cell="remaining">{{ number_format($order->remaining) }}</td>
                            <td>
                                @php
                                    // Ghi chu tung ma hang (nhap luc len don) va ghi chu kiem don.
                                    $itemNotes = $codeSummary
                                        ->filter(fn ($code) => $code['note'] !== '')
                                        ->map(fn ($code) => $code['code'] . ' ' . $code['size'] . ': ' . $code['note']);
                                    $checkNote = trim((string) $order->check_note);
                                    $noteTitle = $itemNotes->push($checkNote ? 'Kiểm đơn: ' . $checkNote : null)
                                        ->filter()
                                        ->implode("
");
                                @endphp
                                @if ($noteTitle === '')
                                    <span class="muted">—</span>
                                @else
                                    <div class="note-cell" title="{{ $noteTitle }}">
                                        @foreach ($codeSummary->filter(fn ($code) => $code['note'] !== '') as $code)
                                            <div class="note-line">
                                                <span class="note-tag">{{ $code['code'] }} {{ $code['size'] }}</span>
                                                {{ $code['note'] }}
                                            </div>
                                        @endforeach
                                        @if ($checkNote !== '')
                                            <div class="note-line">
                                                <span class="note-tag is-check">Kiểm đơn</span>
                                                {{ $checkNote }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
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
                        @if ($codeSummary->isNotEmpty())
                            {{-- Hang so ma hang: an mac dinh, mo bang nut "So N ma"
                                 hoac nut "So tat ca ma hang" tren thanh cong cu. --}}
                            <tr class="codes-row row-{{ $order->statusColorKey() }}" id="codes-panel-{{ $order->id }}"
                                data-codes-panel="{{ $order->id }}" hidden>
                                <td colspan="17">
                                    <div class="codes-panel">
                                        <div class="codes-panel__head">
                                            <span class="codes-panel__title">{{ $order->order_name }}</span>
                                            <span class="muted">{{ $codeSummary->count() }} mã · tổng {{ number_format($codeTotal) }} cái</span>
                                        </div>
                                        <div class="codes-grid">
                                            @foreach ($codeSummary as $code)
                                                <div class="code-card">
                                                    @if ($code['image'])
                                                        <button type="button" class="code-thumb has-image"
                                                            data-zoom-src="{{ $code['image'] }}"
                                                            data-zoom-alt="{{ $code['code'] }} - {{ $code['name'] }}"
                                                            aria-label="Phóng to ảnh {{ $code['code'] }}">
                                                            <img src="{{ $code['image'] }}" alt="{{ $code['code'] }}" loading="lazy">
                                                        </button>
                                                    @else
                                                        <div class="code-thumb">CHƯA CÓ ẢNH</div>
                                                    @endif
                                                    <div class="code-card__info">
                                                        <div class="code-card__code">{{ $code['code'] }}</div>
                                                        <div class="code-card__name">{{ $code['name'] ?: '—' }}</div>
                                                        <div class="code-card__meta">
                                                            <span class="code-card__size">{{ $code['size'] }}</span>
                                                            <span class="code-card__qty">×{{ number_format($code['quantity']) }}</span>
                                                        </div>
                                                        @if ($code['note'] !== '')
                                                            <div class="code-card__note" title="{{ $code['note'] }}">📝 {{ $code['note'] }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td class="empty" colspan="17">Chưa có đơn hàng phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            {{-- Trinh duyet tai san trang ke trong luc dang doc trang hien tai,
                 nen bam sang la hien gan nhu tuc thi. Khong doi kien truc gi. --}}
            @if ($orders->hasMorePages())
                <link rel="prefetch" href="{{ $orders->nextPageUrl() }}" as="document">
            @endif
            @if (! $orders->onFirstPage())
                <link rel="prefetch" href="{{ $orders->previousPageUrl() }}" as="document">
            @endif

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
    @include('orders.modal-data')
    @include('orders.status-ajax')
    @include('orders.codes-panel')
    @include('orders.codes-modal')
    @include('orders.status-confirm-modal')
    @include('orders.check-modal')
    @include('orders.reminders-popup')
    </div>
</div>
</body>
</html>

