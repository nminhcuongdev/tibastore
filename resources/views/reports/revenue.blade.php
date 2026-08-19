<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Báo cáo doanh thu</title>
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
        .content { flex: 1; min-width: 0; padding: 28px clamp(18px, 4vw, 48px) 56px; }
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
            margin-bottom: 18px;
        }
        .field { display: grid; gap: 6px; }
        label { color: #7a344c; font-size: 13px; font-weight: 700; }
        input, select {
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 8px;
            color: #3f2730;
            font-family: inherit;
            font-size: 15px;
            min-height: 44px;
            padding: 10px 13px;
        }
        input:focus, select:focus {
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
        .button.secondary { background: #fff; border: 1px solid #ebc5d2; color: #8b2f4d; }
        .cards {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            margin-bottom: 22px;
        }
        .card {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 10px;
            box-shadow: 0 14px 34px rgba(117, 44, 69, .07);
            padding: 16px 18px;
        }
        .card .label { color: #8b6672; font-size: 13px; font-weight: 700; margin-bottom: 6px; }
        .card .value { color: #6f253f; font-size: 24px; font-weight: 900; }
        .card.is-primary { background: #be476f; border-color: #be476f; }
        .card.is-primary .label { color: #ffdce8; }
        .card.is-primary .value { color: #fff; }
        h2 {
            color: #6f253f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 22px;
            margin: 26px 0 12px;
        }
        .table-shell {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
            overflow-x: auto;
        }
        table { border-collapse: separate; border-spacing: 0; width: 100%; }
        th, td {
            border-bottom: 1px solid #f7e3e9;
            padding: 11px 13px;
            text-align: right;
            white-space: nowrap;
        }
        th.text, td.text { text-align: left; }
        thead th {
            background: #fff0f4;
            color: #81304c;
            font-size: 12px;
            position: sticky;
            text-transform: uppercase;
            top: 0;
            z-index: 2;
        }
        tbody tr:hover td { background: #fffafb; }
        tfoot td {
            background: #fff0f4;
            border-top: 2px solid #f0d3dc;
            font-weight: 900;
            position: sticky;
            bottom: 0;
        }
        .muted { color: #8b6672; font-size: 12px; }
        .strong { color: #6f253f; font-weight: 900; }
        .zero { color: #c4a9b3; }
        .muted-col { color: #8b6672; }
        .empty { color: #8b6672; padding: 36px; text-align: center; }
        .note {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-left: 5px solid #be476f;
            border-radius: 8px;
            color: #704252;
            font-size: 13px;
            line-height: 1.6;
            margin-top: 22px;
            padding: 14px 16px;
        }
        @media print {
            .toolbar, .note { display: none; }
            .table-shell { max-height: none; overflow: visible; }
        }
    </style>
    @include('partials.compact')
</head>
<body>
<div class="layout">
@include('partials.sidebar', ['active' => 'revenue'])
<main class="content">
    <h1>Báo cáo doanh thu</h1>
    <p class="sub">Doanh thu tách theo <strong>nguồn hàng</strong> của đơn. Doanh thu mỗi đơn = tiền hàng (giá thuê &times; số lượng của mọi dòng).</p>

    <form class="toolbar" method="GET" action="{{ route('reports.revenue') }}">
        <div class="field">
            <label for="mode">Xem theo</label>
            <select id="mode" name="mode">
                @foreach ($modes as $value => $label)
                    <option value="{{ $value }}" @selected($mode === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="basis">Tính theo mốc</label>
            <select id="basis" name="basis">
                @foreach ($dateBases as $value => $label)
                    <option value="{{ $value }}" @selected($basis === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @if ($mode === 'week')
            <div class="field">
                <label for="month">Tháng</label>
                <input id="month" name="month" type="month" value="{{ $month->format('Y-m') }}">
            </div>
        @else
            <div class="field">
                <label for="from">Từ ngày</label>
                <input id="from" name="from" type="date" value="{{ $dateFrom->format('Y-m-d') }}">
            </div>
            <div class="field">
                <label for="to">Đến ngày</label>
                <input id="to" name="to" type="date" value="{{ $dateTo->format('Y-m-d') }}">
            </div>
        @endif
        <button class="button" type="submit">Xem báo cáo</button>
        <a class="button secondary" href="{{ route('reports.revenue') }}">Đặt lại</a>
    </form>

    <div class="cards">
        <div class="card is-primary">
            <div class="label">Tổng doanh thu</div>
            <div class="value">{{ number_format($grandTotal['total']) }}</div>
        </div>
        <div class="card">
            <div class="label">Tiền bồi thường</div>
            <div class="value">{{ number_format($grandTotal['compensation']) }}</div>
        </div>
        <div class="card">
            <div class="label">Số đơn</div>
            <div class="value">{{ number_format($grandTotal['orders']) }}</div>
        </div>
    </div>

    <h2>Theo nguồn hàng &mdash; {{ $dateFrom->format('d/m/Y') }} đến {{ $dateTo->format('d/m/Y') }}</h2>
    <div class="table-shell">
        <table>
            <thead>
                <tr>
                    <th class="text">Nguồn hàng</th>
                    <th>Số đơn</th>
                    <th>Doanh thu</th>
                    <th>Tỷ trọng</th>
                    <th>Bồi thường</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summary as $row)
                    <tr>
                        <td class="text strong">{{ $row['label'] }}</td>
                        <td>{{ number_format($row['orders']) }}</td>
                        <td class="strong">{{ number_format($row['total']) }}</td>
                        <td>{{ $grandTotal['total'] > 0 ? number_format($row['total'] * 100 / $grandTotal['total'], 1) . '%' : '—' }}</td>
                        <td class="muted-col">{{ number_format($row['compensation']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td class="text">Tổng cộng</td>
                    <td>{{ number_format($grandTotal['orders']) }}</td>
                    <td>{{ number_format($grandTotal['total']) }}</td>
                    <td>{{ $grandTotal['total'] > 0 ? '100%' : '—' }}</td>
                    <td>{{ number_format($grandTotal['compensation']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($mode !== 'range')
        <h2>{{ $mode === 'week' ? 'Chi tiết theo tuần' : 'Chi tiết theo ngày' }}</h2>
        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th class="text">{{ $mode === 'week' ? 'Tuần' : 'Ngày' }}</th>
                        @foreach ($sources as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th>Số đơn</th>
                        <th>Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="text">
                                <div class="strong">{{ $row['label'] }}</div>
                                <div class="muted">{{ $row['sub'] }}</div>
                            </td>
                            @foreach ($sources as $key => $label)
                                <td class="{{ $row['by_source'][$key]['total'] === 0 ? 'zero' : '' }}">
                                    {{ number_format($row['by_source'][$key]['total']) }}
                                </td>
                            @endforeach
                            <td>{{ number_format($row['total']['orders']) }}</td>
                            <td class="strong">{{ number_format($row['total']['total']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty" colspan="{{ count($sources) + 3 }}">Không có dữ liệu trong khoảng này.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td class="text">Tổng cộng</td>
                        @foreach ($sources as $key => $label)
                            <td>{{ number_format($summary[$key]['total'] ?? 0) }}</td>
                        @endforeach
                        <td>{{ number_format($grandTotal['orders']) }}</td>
                        <td>{{ number_format($grandTotal['total']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    <div class="note">
        <strong>Cách tính:</strong> doanh thu mỗi đơn = tiền hàng, tức giá thuê &times; số lượng của mọi dòng hàng trong đơn.
        Cột <strong>Bồi thường</strong> chỉ để tham khảo, <strong>không</strong> cộng vào doanh thu. Tiền ship không đưa vào báo cáo này.
        Đơn được xếp vào kỳ theo mốc <strong>{{ $dateBases[$basis] }}</strong> &mdash; đổi mốc ở ô "Tính theo mốc" nếu bạn muốn tính theo ngày lấy/diễn/trả.
        Báo cáo đếm mọi đơn trong kỳ, không phân biệt trạng thái hay đã thu tiền hay chưa.
    </div>
</main>
</div>
</body>
</html>
