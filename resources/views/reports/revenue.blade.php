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
            background: #f5f8ff;
            color: #1e293b;
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        .content { flex: 1; min-width: 0; padding: 28px clamp(18px, 4vw, 48px) 56px; }
        h1 {
            color: #1e3a5f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(28px, 4vw, 40px);
            margin: 0 0 6px;
        }
        .sub { color: #475569; margin: 0 0 20px; }
        .toolbar {
            align-items: end;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }
        .field { display: grid; gap: 6px; }
        label { color: #334e75; font-size: 13px; font-weight: 700; }
        input, select {
            background: #fff;
            border: 1px solid #c3d4ed;
            border-radius: 8px;
            color: #1e293b;
            font-family: inherit;
            font-size: 15px;
            min-height: 44px;
            padding: 10px 13px;
        }
        input:focus, select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .16);
            outline: none;
        }
        .button {
            align-items: center;
            background: #2563eb;
            border: 0;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            font-weight: 800;
            min-height: 44px;
            padding: 10px 16px;
        }
        .button.secondary { background: #fff; border: 1px solid #c3d4ed; color: #1e40af; }
        .basis-group { display: flex; flex-wrap: wrap; gap: 8px; }
        .check {
            align-items: center;
            background: #fff;
            border: 1px solid #c3d4ed;
            border-radius: 999px;
            color: #1e40af;
            cursor: pointer;
            display: inline-flex;
            font-size: 14px;
            font-weight: 700;
            gap: 7px;
            min-height: 44px;
            padding: 8px 14px;
            user-select: none;
        }
        .check.is-on { background: #e5efff; border-color: #3b82f6; color: #1e40af; }
        .check input { accent-color: #2563eb; cursor: pointer; margin: 0; min-height: 0; width: auto; }
        .cards {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            margin-bottom: 22px;
        }
        .card {
            background: #fff;
            border: 1px solid #d8e4f5;
            border-radius: 10px;
            box-shadow: 0 14px 34px rgba(30, 64, 175, .07);
            padding: 16px 18px;
        }
        .card .label { color: #64748b; font-size: 13px; font-weight: 700; margin-bottom: 6px; }
        .card .value { color: #1e3a5f; font-size: 24px; font-weight: 900; }
        .card.is-primary { background: #2563eb; border-color: #2563eb; }
        .card.is-primary .label { color: #dbeafe; }
        .card.is-primary .value { color: #fff; }
        h2 {
            color: #1e3a5f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 22px;
            margin: 26px 0 12px;
        }
        .table-shell {
            background: #fff;
            border: 1px solid #d8e4f5;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(30, 64, 175, .08);
            overflow-x: auto;
        }
        table { border-collapse: separate; border-spacing: 0; width: 100%; }
        th, td {
            border-bottom: 1px solid #e7eef8;
            padding: 11px 13px;
            text-align: right;
            white-space: nowrap;
        }
        th.text, td.text { text-align: left; }
        thead th {
            background: #eff6ff;
            color: #1e3a5f;
            font-size: 12px;
            position: sticky;
            text-transform: uppercase;
            top: 0;
            z-index: 2;
        }
        tbody tr:hover td { background: #f8fbff; }
        tfoot td {
            background: #eff6ff;
            border-top: 2px solid #d8e4f5;
            font-weight: 900;
            position: sticky;
            bottom: 0;
        }
        .muted { color: #64748b; font-size: 12px; }
        .strong { color: #1e3a5f; font-weight: 900; }
        .zero { color: #a7b9d0; }
        .muted-col { color: #64748b; }
        .empty { color: #64748b; padding: 36px; text-align: center; }
        .note {
            background: #fff;
            border: 1px solid #d8e4f5;
            border-left: 5px solid #2563eb;
            border-radius: 8px;
            color: #475569;
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
            <label>Tính theo mốc <span class="muted">(chọn nhiều)</span></label>
            <div class="basis-group">
                @foreach ($dateBases as $value => $label)
                    <label class="check {{ in_array($value, $bases, true) ? 'is-on' : '' }}">
                        <input type="checkbox" name="bases[]" value="{{ $value }}"
                               @checked(in_array($value, $bases, true))>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
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
        Đang tính theo mốc: <strong>{{ implode(', ', array_map(fn ($key) => $dateBases[$key], $bases)) }}</strong>.
        Đơn được đưa vào kỳ nếu <strong>bất kỳ</strong> mốc đã chọn rơi vào khoảng ngày, và mỗi đơn chỉ được <strong>đếm một lần</strong> dù khớp nhiều mốc.
        @if ($mode !== 'range')
            Ở bảng chi tiết, đơn được xếp vào dòng theo mốc <strong>sớm nhất</strong> trong các mốc đã chọn.
        @endif
        Báo cáo đếm mọi đơn trong kỳ, không phân biệt trạng thái hay đã thu tiền hay chưa.
    </div>
</main>
</div>
</body>
</html>
