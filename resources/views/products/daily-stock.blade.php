<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tồn kho theo ngày</title>
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
        .nav { align-items: center; display: flex; flex-wrap: wrap; gap: 10px; }
        .nav a, .logout {
            background: #fff;
            border: 1px solid #e8b8c8;
            border-radius: 999px;
            color: #8b2f4d;
            cursor: pointer;
            font-weight: 700;
            padding: 10px 16px;
        }
        .nav a.active { background: #be476f; color: #fff; }
        .content { padding: 28px clamp(18px, 5vw, 56px) 56px; }
        h1 {
            color: #6f253f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(28px, 4vw, 40px);
            margin: 0 0 6px;
        }
        .sub { color: #704252; margin: 0 0 20px; }
        .toolbar {
            align-items: end;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 14px;
        }
        .field { display: grid; gap: 6px; }
        label { color: #7a344c; font-size: 13px; font-weight: 700; }
        input {
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 8px;
            color: #3f2730;
            font-size: 15px;
            min-height: 44px;
            padding: 10px 13px;
        }
        input:focus { border-color: #c9577d; box-shadow: 0 0 0 3px rgba(201, 87, 125, .16); outline: none; }
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
        .legend { color: #704252; display: flex; flex-wrap: wrap; font-size: 13px; gap: 16px; margin-bottom: 14px; }
        .legend span { align-items: center; display: inline-flex; gap: 6px; }
        .dot { border-radius: 3px; display: inline-block; height: 14px; width: 14px; }
        .dot.ok { background: #e8f7ef; border: 1px solid #9bd9ba; }
        .dot.low { background: #fff7d6; border: 1px solid #e5c96a; }
        .dot.out { background: #ffe1e1; border: 1px solid #eaa; }
        .table-shell {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
            max-height: 78vh;
            overflow: auto;
        }
        table { border-collapse: separate; border-spacing: 0; width: 100%; }
        th, td { border-bottom: 1px solid #f7e3e9; border-right: 1px solid #f7e3e9; padding: 8px 10px; text-align: center; white-space: nowrap; }
        thead th {
            background: #fff0f4;
            color: #81304c;
            font-size: 12px;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        thead th.today { background: #ffd9e6; }
        th.prod, td.prod {
            background: #fff;
            left: 0;
            min-width: 220px;
            position: sticky;
            text-align: left;
            z-index: 1;
        }
        thead th.prod { z-index: 3; background: #fff0f4; }
        .code { color: #a13b60; font-weight: 800; }
        .muted { color: #8b6672; font-size: 12px; }
        .dow { color: #a06; font-size: 11px; font-weight: 700; }
        .cell { font-weight: 800; }
        .cell-ok { background: #e8f7ef; color: #1f7d55; }
        .cell-low { background: #fff7d6; color: #8a5a00; }
        .cell-out { background: #ffe1e1; color: #b4233f; }
        td.today, thead th.today { box-shadow: inset 2px 0 0 #be476f, inset -2px 0 0 #be476f; }
        .empty { color: #8b6672; padding: 36px; text-align: center; }
        @media (max-width: 760px) {
            .topbar { align-items: stretch; flex-direction: column; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('products.index') }}">Tiba Boutique</a>
        <div class="nav">
            <a href="{{ route('products.index') }}">Kho hàng</a>
            <a class="active" href="{{ route('products.daily-stock') }}">Tồn kho theo ngày</a>
            <a href="{{ route('orders.index') }}">Đơn hàng</a>
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

    <main class="content">
        <h1>Tồn kho theo ngày</h1>
        <p class="sub">Số lượng <strong>khả dụng</strong> của từng sản phẩm theo mỗi ngày = tồn hiện có + hàng nhập dự kiến về − số đang cho thuê (đơn phủ ngày đó).</p>

        <form class="toolbar" method="GET" action="{{ route('products.daily-stock') }}">
            <div class="field">
                <label for="q">Tìm mã / tên sản phẩm</label>
                <input id="q" name="q" type="search" value="{{ $query }}" placeholder="VD: N20, váy...">
            </div>
            <div class="field">
                <label for="from">Từ ngày</label>
                <input id="from" name="from" type="date" value="{{ $dateFrom->format('Y-m-d') }}">
            </div>
            <div class="field">
                <label for="to">Đến ngày</label>
                <input id="to" name="to" type="date" value="{{ $dateTo->format('Y-m-d') }}">
            </div>
            <button class="button" type="submit">Xem</button>
            <a class="button secondary" href="{{ route('products.daily-stock') }}">Về hôm nay</a>
        </form>

        <div class="legend">
            <span><i class="dot ok"></i> Còn nhiều</span>
            <span><i class="dot low"></i> Còn ít (1–2)</span>
            <span><i class="dot out"></i> Hết / vượt (≤ 0)</span>
            <span class="muted">Tối đa 60 ngày mỗi lần xem. Cột viền hồng là hôm nay.</span>
        </div>

        @php $todayStr = now()->toDateString(); $dow = ['CN','T2','T3','T4','T5','T6','T7']; @endphp

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th class="prod">Sản phẩm</th>
                        @foreach ($dates as $date)
                            <th class="{{ $date->toDateString() === $todayStr ? 'today' : '' }}">
                                {{ $date->format('d/m') }}
                                <div class="dow">{{ $dow[$date->dayOfWeek] }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="prod">
                                <div><span class="code">{{ $product->code }}</span> — size {{ $product->size }}</div>
                                <div class="muted">{{ $product->name }} · tồn hiện tại: {{ number_format($product->stock_quantity) }}</div>
                            </td>
                            @foreach ($dates as $date)
                                @php
                                    $v = $availability[$product->id][$date->toDateString()] ?? 0;
                                    $cls = $v <= 0 ? 'cell-out' : ($v <= 2 ? 'cell-low' : 'cell-ok');
                                    $isToday = $date->toDateString() === $todayStr;
                                @endphp
                                <td class="cell {{ $cls }} {{ $isToday ? 'today' : '' }}">{{ $v }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td class="empty" colspan="{{ count($dates) + 1 }}">Không có sản phẩm phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
