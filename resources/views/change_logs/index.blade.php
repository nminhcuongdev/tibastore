<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lịch sử thay đổi</title>
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
            margin: 0 0 6px;
        }
        .sub { color: #704252; }
        .toolbar {
            align-items: end;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 14px;
        }
        .field { display: grid; gap: 5px; }
        label { color: #7a344c; font-weight: 700; }
        input, select {
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 8px;
            color: #3f2730;
            font-family: inherit;
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
        }
        .button.secondary { background: #fff; border: 1px solid #ebc5d2; color: #8b2f4d; }
        .table-shell {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
            overflow-x: auto;
        }
        table { border-collapse: collapse; width: 100%; }
        th, td {
            border-bottom: 1px solid #f7e3e9;
            text-align: left;
            vertical-align: top;
        }
        thead th {
            background: #fff0f4;
            color: #81304c;
            text-transform: uppercase;
            white-space: nowrap;
        }
        tbody tr:hover td { background: #fffafb; }
        .muted { color: #8b6672; }
        .nowrap { white-space: nowrap; }
        .subject { color: #a13b60; font-weight: 800; }
        .badge {
            border-radius: 999px;
            display: inline-block;
            font-weight: 900;
            padding: 3px 9px;
            white-space: nowrap;
        }
        .badge.type-order { background: #eaf2fd; color: #2f5fa6; }
        .badge.type-product { background: #f6ecfb; color: #7d3aa3; }
        .badge.ev-created { background: #e8f7ef; color: #247857; }
        .badge.ev-updated { background: #fff7d6; color: #8a5a00; }
        .badge.ev-deleted { background: #fdecec; color: #b4233f; }
        /* Mỗi dòng thay đổi: tên trường, giá trị cũ gạch ngang, giá trị mới đậm. */
        .changes { display: grid; gap: 4px; margin: 0; min-width: 320px; padding: 0; }
        .change { display: flex; flex-wrap: wrap; gap: 6px; }
        .change .name { color: #7a344c; font-weight: 800; }
        .change .old {
            background: #fdecec;
            border-radius: 5px;
            color: #b4233f;
            padding: 1px 6px;
            text-decoration: line-through;
        }
        .change .arrow { color: #8b6672; font-weight: 900; }
        .change .new {
            background: #e8f7ef;
            border-radius: 5px;
            color: #247857;
            font-weight: 800;
            padding: 1px 6px;
        }
        .change .empty-val { color: #a08693; font-style: italic; }
        .empty { color: #8b6672; padding: 36px; text-align: center; }
        .pagination {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
            margin-top: 16px;
        }
        .pages { display: flex; flex-wrap: wrap; gap: 6px; }
        .pages a, .pages span {
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 8px;
            color: #8b2f4d;
            padding: 6px 10px;
        }
        .pages .active { background: #be476f; border-color: #be476f; color: #fff; }
    </style>
    @include('partials.compact')
</head>
<body>
<div class="layout">
@include('partials.sidebar', ['active' => 'change-logs'])
<main class="content">
    <h1>Lịch sử thay đổi</h1>
    <p class="sub">Mọi thay đổi của đơn hàng và sản phẩm: đổi từ giá trị nào sang giá trị nào, ai làm và lúc nào.</p>

    <form class="toolbar" method="GET" action="{{ route('change-logs.index') }}">
        <div class="field">
            <label for="type">Loại</label>
            <select id="type" name="type">
                <option value="">Tất cả</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="event">Hành động</label>
            <select id="event" name="event">
                <option value="">Tất cả</option>
                @foreach ($events as $value => $label)
                    <option value="{{ $value }}" @selected($filters['event'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="q">Mã đơn / mã hàng</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="VD: N16, #12...">
        </div>
        <div class="field">
            <label for="actor">Người thực hiện</label>
            <select id="actor" name="actor">
                <option value="">Tất cả</option>
                @foreach ($actors as $name)
                    <option value="{{ $name }}" @selected($filters['actor'] === $name)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="from">Từ ngày</label>
            <input id="from" name="from" type="date" value="{{ $filters['from'] }}">
        </div>
        <div class="field">
            <label for="to">Đến ngày</label>
            <input id="to" name="to" type="date" value="{{ $filters['to'] }}">
        </div>
        <button class="button" type="submit">Lọc</button>
        <a class="button secondary" href="{{ route('change-logs.index') }}">Xóa lọc</a>
    </form>

    <div class="table-shell">
        <table>
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Loại</th>
                    <th>Bản ghi</th>
                    <th>Hành động</th>
                    <th>Người thực hiện</th>
                    <th>Nội dung thay đổi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="badge {{ $log->loggable_type === \App\Models\Order::class ? 'type-order' : 'type-product' }}">
                                {{ $log->typeLabel() }}
                            </span>
                        </td>
                        <td class="subject">{{ $log->subject_label }}</td>
                        <td><span class="badge ev-{{ $log->event }}">{{ $log->eventLabel() }}</span></td>
                        <td class="nowrap">{{ $log->actorName() }}</td>
                        <td>
                            @php $entries = $log->change_set ?? []; @endphp
                            @if ($log->event === 'deleted')
                                <span class="muted">Bản ghi đã bị xóa.</span>
                            @elseif (empty($entries))
                                <span class="muted">Không có thay đổi nào được ghi.</span>
                            @else
                                <div class="changes">
                                    @foreach ($entries as $entry)
                                        <div class="change">
                                            <span class="name">{{ $entry['label'] ?? $entry['field'] }}:</span>
                                            @if (($entry['old'] ?? null) === null)
                                                <span class="empty-val">(trống)</span>
                                            @else
                                                <span class="old">{{ $entry['old'] }}</span>
                                            @endif
                                            <span class="arrow">&rarr;</span>
                                            @if (($entry['new'] ?? null) === null)
                                                <span class="empty-val">(trống)</span>
                                            @else
                                                <span class="new">{{ $entry['new'] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="6">Chưa có thay đổi nào được ghi lại.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($logs->hasPages())
        <nav class="pagination" aria-label="Phân trang">
            <div class="muted">Trang {{ $logs->currentPage() }} / {{ $logs->lastPage() }} &mdash; {{ number_format($logs->total()) }} bản ghi</div>
            <div class="pages">
                @if ($logs->onFirstPage())
                    <span>Trước</span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}">Trước</a>
                @endif
                @if ($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}">Sau</a>
                @else
                    <span>Sau</span>
                @endif
            </div>
        </nav>
    @endif
</main>
</div>
</body>
</html>
