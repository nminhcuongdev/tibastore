<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chi tiết sản phẩm</title>
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
                linear-gradient(115deg, rgba(255, 255, 255, .94), rgba(255, 235, 242, .78)),
                url("https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1600&q=80");
            background-position: center;
            background-size: cover;
            border-bottom: 1px solid #f5d9e2;
            padding: 44px clamp(18px, 5vw, 56px);
        }
        .hero h1 {
            color: #6f253f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(34px, 5vw, 56px);
            line-height: 1.05;
            margin: 0 0 12px;
        }
        .hero p {
            color: #704252;
            font-size: 17px;
            line-height: 1.6;
            margin: 0;
            max-width: 720px;
        }
        .content { padding: 28px clamp(18px, 5vw, 56px) 56px; }
        .summary {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 20px;
        }
        .metric,
        .panel {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
        }
        .metric {
            padding: 18px;
        }
        .metric-label {
            color: #8b6672;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .metric-value {
            color: #542033;
            font-size: 24px;
            font-weight: 900;
        }
        .panel {
            margin-bottom: 22px;
            overflow: hidden;
        }
        .panel-head {
            align-items: center;
            background: #fff0f4;
            border-bottom: 1px solid #f7d7e1;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 18px;
        }
        .panel-head h2 {
            color: #6f253f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 24px;
            margin: 0;
        }
        .panel-head p {
            color: #8b6672;
            line-height: 1.5;
            margin: 4px 0 0;
        }
        .chart-wrap {
            overflow-x: auto;
            padding: 18px;
        }
        .chart {
            min-width: 860px;
        }
        .axis-label {
            fill: #8b6672;
            font-size: 12px;
        }
        .axis-value {
            fill: #542033;
            font-size: 13px;
            font-weight: 800;
        }
        .grid-line {
            stroke: #f7d7e1;
            stroke-width: 1;
        }
        .line {
            fill: none;
            stroke: #be476f;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 4;
        }
        .area {
            fill: rgba(190, 71, 111, .1);
        }
        .dot {
            fill: #be476f;
            stroke: #fff;
            stroke-width: 3;
        }
        .point-label-bg {
            fill: #fff;
            stroke: #f0c7d3;
            stroke-width: 1;
        }
        .point-label {
            fill: #6f253f;
            font-size: 13px;
            font-weight: 900;
            text-anchor: middle;
        }
        .zero-line {
            stroke: #f0d3dc;
            stroke-dasharray: 5 5;
            stroke-width: 2;
        }
        .timeline {
            display: grid;
            gap: 10px;
            padding: 0 18px 18px;
        }
        .range-form {
            align-items: end;
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(180px, 1fr)) auto;
            padding: 18px;
        }
        .range-field {
            display: grid;
            gap: 7px;
        }
        .range-field label {
            color: #7a344c;
            font-size: 13px;
            font-weight: 800;
        }
        .range-field input {
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 8px;
            color: #3f2730;
            font-size: 15px;
            min-height: 44px;
            padding: 10px 13px;
            width: 100%;
        }
        .range-field input:focus {
            border-color: #c9577d;
            box-shadow: 0 0 0 3px rgba(201, 87, 125, .16);
            outline: none;
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
        .forecast-summary {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            padding: 0 18px 18px;
        }
        .forecast-card {
            background: #fff8fa;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            padding: 14px;
        }
        .forecast-card span {
            color: #8b6672;
            display: block;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .forecast-card strong {
            color: #542033;
            font-size: 22px;
        }
        .day-events {
            display: grid;
            gap: 6px;
        }
        .day-event {
            color: #704252;
            font-size: 13px;
            line-height: 1.4;
        }
        .forecast-table {
            table-layout: fixed;
        }
        .forecast-table th:nth-child(1),
        .forecast-table td:nth-child(1) {
            width: 140px;
        }
        .forecast-table th:nth-child(2),
        .forecast-table td:nth-child(2),
        .forecast-table th:nth-child(3),
        .forecast-table td:nth-child(3) {
            text-align: right;
        }
        .forecast-table th:nth-child(4),
        .forecast-table td:nth-child(4) {
            text-align: left;
            width: auto;
        }
        .forecast-table td {
            height: 64px;
        }
        .forecast-table .event-change,
        .forecast-table .event-stock {
            white-space: nowrap;
        }
        .event {
            align-items: center;
            border: 1px solid #f5d9e2;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: 110px 1fr 110px 120px;
            padding: 12px 14px;
        }
        .event-change {
            font-weight: 900;
            text-align: right;
        }
        .event-change.positive { color: #247857; }
        .event-change.negative { color: #b4233f; }
        .event-stock {
            color: #542033;
            font-weight: 900;
            text-align: right;
        }
        table {
            border-collapse: collapse;
            min-width: 1040px;
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
        .table-shell { overflow-x: auto; }
        .code {
            color: #a13b60;
            font-weight: 900;
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
        .status-len_don { background: #fff4f7; color: #9d345a; }
        .status-da_gui { background: #fff7d6; color: #8a5a00; }
        .status-thanh_cong { background: #e8f7ef; color: #247857; }
        .empty {
            color: #8b6672;
            padding: 34px;
            text-align: center;
        }
        .pagination {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
            padding: 16px 18px;
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
        @media (max-width: 900px) {
            .summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .range-form,
            .forecast-summary {
                grid-template-columns: 1fr;
            }
            .topbar { align-items: stretch; flex-direction: column; }
            .nav a, .logout { justify-content: center; width: 100%; }
            .event { grid-template-columns: 1fr; }
            .event-change, .event-stock { text-align: left; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('products.index') }}">Tiba Boutique</a>
        <div class="nav">
            <a class="active" href="{{ route('products.index') }}">Kho hàng</a>
            <a href="{{ route('orders.index') }}">Đơn hàng</a>
            <a href="{{ route('stock-import-histories.index') }}">Lịch sử nhập</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout" type="submit">Đăng xuất</button>
            </form>
        </div>
    </header>

    <section class="hero">
        <h1>{{ $product->code }} - {{ $product->name }}</h1>
        <p>Xem các đơn hàng liên quan và biến động tồn kho dự kiến theo thời gian cho từng lần lên đơn, gửi hàng và hoàn tất.</p>
    </section>

    <main class="content">
        <div class="summary">
            <div class="metric">
                <div class="metric-label">Tồn hiện tại</div>
                <div class="metric-value">{{ number_format($product->stock_quantity) }}</div>
            </div>
            <div class="metric">
                <div class="metric-label">Size</div>
                <div class="metric-value">{{ $product->size }}</div>
            </div>
            <div class="metric">
                <div class="metric-label">Vải</div>
                <div class="metric-value">{{ $product->fabric }}</div>
            </div>
            <div class="metric">
                <div class="metric-label">Tổng đơn</div>
                <div class="metric-value">{{ number_format($product->orders()->count()) }}</div>
            </div>
        </div>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2>Biểu đồ biến động dự kiến</h2>
                    <p>Mốc “Lên đơn” dùng ngày tạo đơn, “Đã gửi” dùng ngày lấy để trừ kho, “Thành công” dùng ngày trả để hoàn kho.</p>
                </div>
            </div>

            @if (count($timeline) > 0)
                @php
                    $width = 860;
                    $height = 260;
                    $left = 54;
                    $right = 28;
                    $top = 28;
                    $bottom = 64;
                    $plotWidth = $width - $left - $right;
                    $plotHeight = $height - $top - $bottom;
                    $values = collect($timeline)->pluck('estimated_quantity');
                    $minValue = min(0, $values->min());
                    $maxValue = max($product->stock_quantity, $values->max(), 1);
                    $range = max(1, $maxValue - $minValue);
                    $count = max(1, count($timeline) - 1);
                    $polyline = collect($timeline)->map(function ($point, $index) use ($left, $top, $plotWidth, $plotHeight, $minValue, $range, $count) {
                        $x = $left + ($plotWidth * $index / $count);
                        $y = $top + ($plotHeight - (($point['estimated_quantity'] - $minValue) / $range * $plotHeight));
                        return round($x, 2) . ',' . round($y, 2);
                    })->implode(' ');
                    $areaPoints = $left . ',' . ($height - $bottom) . ' ' . $polyline . ' ' . ($width - $right) . ',' . ($height - $bottom);
                    $zeroY = $top + ($plotHeight - ((0 - $minValue) / $range * $plotHeight));
                    $middleValue = (int) round(($minValue + $maxValue) / 2);
                    $middleY = $top + ($plotHeight - (($middleValue - $minValue) / $range * $plotHeight));
                @endphp

                <div class="chart-wrap">
                    <svg class="chart" width="{{ $width }}" height="{{ $height }}" viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="Biểu đồ biến động tồn kho dự kiến">
                        <line class="grid-line" x1="{{ $left }}" y1="{{ $top }}" x2="{{ $width - $right }}" y2="{{ $top }}"></line>
                        <line class="grid-line" x1="{{ $left }}" y1="{{ $middleY }}" x2="{{ $width - $right }}" y2="{{ $middleY }}"></line>
                        <line class="grid-line" x1="{{ $left }}" y1="{{ $height - $bottom }}" x2="{{ $width - $right }}" y2="{{ $height - $bottom }}"></line>
                        <line class="zero-line" x1="{{ $left }}" y1="{{ $zeroY }}" x2="{{ $width - $right }}" y2="{{ $zeroY }}"></line>
                        <text class="axis-value" x="8" y="{{ $top + 4 }}">{{ $maxValue }}</text>
                        <text class="axis-value" x="8" y="{{ $middleY + 4 }}">{{ $middleValue }}</text>
                        <text class="axis-value" x="8" y="{{ $height - $bottom + 4 }}">{{ $minValue }}</text>
                        <polygon class="area" points="{{ $areaPoints }}"></polygon>
                        <polyline class="line" points="{{ $polyline }}"></polyline>

                        @foreach ($timeline as $index => $point)
                            @php
                                $x = $left + ($plotWidth * $index / $count);
                                $y = $top + ($plotHeight - (($point['estimated_quantity'] - $minValue) / $range * $plotHeight));
                                $labelY = max(18, $y - 16);
                            @endphp
                            <circle class="dot" cx="{{ $x }}" cy="{{ $y }}" r="6">
                                <title>{{ $point['display_date'] }} - {{ $point['label'] }}: tồn ước tính {{ $point['estimated_quantity'] }}</title>
                            </circle>
                            <rect class="point-label-bg" x="{{ $x - 19 }}" y="{{ $labelY - 16 }}" width="38" height="22" rx="8"></rect>
                            <text class="point-label" x="{{ $x }}" y="{{ $labelY }}">{{ $point['estimated_quantity'] }}</text>
                            <text class="axis-label" x="{{ $x - 34 }}" y="{{ $height - 34 }}">{{ $point['display_date'] }}</text>
                            <text class="axis-label" x="{{ $x - 26 }}" y="{{ $height - 16 }}">{{ $point['label'] }}</text>
                        @endforeach
                    </svg>
                </div>

                <div class="timeline">
                    @foreach ($timeline as $point)
                        <div class="event">
                            <div class="code">{{ $point['display_date'] }}</div>
                            <div>
                                <div class="name">{{ $point['label'] }} - {{ $point['order_name'] }}</div>
                                <div class="muted">Trạng thái hiện tại: {{ $point['status'] }}</div>
                            </div>
                            <div class="event-change {{ $point['quantity_change'] >= 0 ? 'positive' : 'negative' }}">
                                {{ $point['quantity_change'] > 0 ? '+' : '' }}{{ number_format($point['quantity_change']) }}
                            </div>
                            <div class="event-stock">Ước tính: {{ number_format($point['estimated_quantity']) }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty">Sản phẩm này chưa có đơn hàng để lập biểu đồ.</div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2>Theo dõi biến động theo ngày</h2>
                    <p>Chọn khoảng thời gian để xem từng ngày kho sẽ tăng, giảm hoặc giữ nguyên dựa trên các đơn hàng của sản phẩm này.</p>
                </div>
            </div>

            <form class="range-form" method="GET" action="{{ route('products.track', $product) }}">
                <div class="range-field">
                    <label for="date_from">Từ ngày</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $dateFrom->format('Y-m-d') }}">
                </div>
                <div class="range-field">
                    <label for="date_to">Đến ngày</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $dateTo->format('Y-m-d') }}">
                </div>
                <button class="button" type="submit">Xem biến động</button>
            </form>

            <div class="forecast-summary">
                <div class="forecast-card">
                    <span>Tổng trừ dự kiến</span>
                    <strong>-{{ number_format($dailyForecast['total_decrease']) }}</strong>
                </div>
                <div class="forecast-card">
                    <span>Tổng hoàn dự kiến</span>
                    <strong>+{{ number_format($dailyForecast['total_increase']) }}</strong>
                </div>
                <div class="forecast-card">
                    <span>Chênh lệch trong kỳ</span>
                    <strong>{{ $dailyForecast['net_change'] > 0 ? '+' : '' }}{{ number_format($dailyForecast['net_change']) }}</strong>
                </div>
            </div>

            <div class="table-shell">
                <table class="forecast-table">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Thay đổi</th>
                            <th>Tồn ước tính cuối ngày</th>
                            <th>Đơn hàng tác động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dailyForecast['rows'] as $row)
                            <tr>
                                <td class="code">{{ $row['display_date'] }}</td>
                                <td class="event-change {{ $row['quantity_change'] >= 0 ? 'positive' : 'negative' }}">
                                    {{ $row['quantity_change'] > 0 ? '+' : '' }}{{ number_format($row['quantity_change']) }}
                                </td>
                                <td class="event-stock">{{ number_format($row['estimated_quantity']) }}</td>
                                <td>
                                    @if (count($row['events']) > 0)
                                        <div class="day-events">
                                            @foreach ($row['events'] as $event)
                                                <div class="day-event">
                                                    <strong>{{ $event['label'] }}</strong> - {{ $event['order_name'] }}
                                                    @if ($event['quantity_change'] !== 0)
                                                        ({{ $event['quantity_change'] > 0 ? '+' : '' }}{{ number_format($event['quantity_change']) }})
                                                    @else
                                                        (không đổi tồn)
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="muted">Không có đơn hàng thay đổi trong ngày này.</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2>Đơn hàng liên quan</h2>
                    <p>Một sản phẩm có thể xuất hiện trong nhiều đơn hàng khác nhau.</p>
                </div>
            </div>
            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Người chốt</th>
                            <th>Ngày lấy</th>
                            <th>Ngày diễn</th>
                            <th>Ngày trả</th>
                            <th>Tên đơn</th>
                            <th>Số lượng</th>
                            <th>Trạng thái</th>
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
                                <td>{{ $order->order_name }}</td>
                                <td>{{ number_format($order->quantity) }}</td>
                                <td>
                                    <span class="status-badge status-{{ $order->status }}">{{ $order->statusLabel() }}</span>
                                </td>
                                <td><a class="page-link" href="{{ route('orders.edit', $order) }}">Sửa</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td class="empty" colspan="8">Chưa có đơn hàng liên quan.</td>
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
        </section>
    </main>
    @include('orders.reminders-popup')
</body>
</html>
