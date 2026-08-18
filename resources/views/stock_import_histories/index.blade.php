<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lịch sử nhập kho</title>
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
                url("https://images.unsplash.com/photo-1487412912498-0447578fcca8?auto=format&fit=crop&w=1600&q=80");
            background-position: center;
            background-size: cover;
            border-bottom: 1px solid #f5d9e2;
            min-height: 240px;
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
        .button {
            align-items: center;
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 8px;
            color: #8b2f4d;
            display: inline-flex;
            font-weight: 800;
            min-height: 44px;
            padding: 10px 16px;
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
            min-width: 980px;
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
        tbody tr:hover { background: #fff9fb; }
        .code {
            color: #a13b60;
            font-weight: 800;
        }
        .name {
            color: #3f2730;
            font-weight: 800;
        }
        .muted {
            color: #8b6672;
            font-size: 13px;
        }
        .quantity {
            color: #247857;
            font-weight: 900;
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
            .nav { width: 100%; }
            .nav a, .logout, .button { justify-content: center; width: 100%; }
        }
    </style>
</head>
<body>
<div class="layout">
@include('partials.sidebar', ['active' => 'stock'])
<div class="page">

    <section class="hero">
        <h1>Lịch sử nhập kho</h1>
        <p>Mỗi lần số lượng tồn của sản phẩm được tăng lên, hệ thống sẽ ghi lại số lượng nhập, tồn trước và tồn sau.</p>
    </section>

    <main class="content">
        <div class="toolbar">
            <form class="search" method="GET" action="{{ route('stock-import-histories.index') }}">
                <label for="q">Tìm theo mã hàng, tên hàng hoặc size</label>
                <input id="q" name="q" type="search" value="{{ $query }}" placeholder="VD: VAY001, váy hoa, size M...">
            </form>

            <a class="button" href="{{ route('stock-import-histories.index') }}">Làm mới</a>
        </div>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Mã hàng</th>
                        <th>Tên sản phẩm</th>
                        <th>Size</th>
                        <th>Người nhập</th>
                        <th>Số lượng nhập</th>
                        <th>Tồn trước</th>
                        <th>Tồn sau</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($histories as $history)
                        <tr>
                            <td>
                                <div class="name">{{ $history->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="code">{{ $history->product->code }}</td>
                            <td>{{ $history->product->name }}</td>
                            <td>{{ $history->product->size }}</td>
                            <td>{{ $history->user?->name ?? 'Hệ thống' }}</td>
                            <td class="quantity">+{{ number_format($history->quantity) }}</td>
                            <td>{{ number_format($history->previous_quantity) }}</td>
                            <td>{{ number_format($history->new_quantity) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty" colspan="8">Chưa có lịch sử nhập kho phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($histories->hasPages())
            <nav class="pagination" aria-label="Phân trang">
                <div class="muted">
                    Hiển thị {{ $histories->firstItem() }}-{{ $histories->lastItem() }} trong {{ $histories->total() }} lần nhập
                </div>
                <div class="pages">
                    @if ($histories->onFirstPage())
                        <span class="page-link">Trước</span>
                    @else
                        <a class="page-link" href="{{ $histories->previousPageUrl() }}">Trước</a>
                    @endif

                    @for ($page = 1; $page <= $histories->lastPage(); $page++)
                        @if ($page === $histories->currentPage())
                            <span class="page-current">{{ $page }}</span>
                        @else
                            <a class="page-link" href="{{ $histories->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor

                    @if ($histories->hasMorePages())
                        <a class="page-link" href="{{ $histories->nextPageUrl() }}">Sau</a>
                    @else
                        <span class="page-link">Sau</span>
                    @endif
                </div>
            </nav>
        @endif
    </main>
    @include('orders.reminders-popup')
    </div>
</div>
</body>
</html>
