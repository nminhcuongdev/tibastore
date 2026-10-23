<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đặt hàng</title>
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
            font-size: clamp(26px, 3.4vw, 36px);
            margin: 0 0 6px;
        }
        .sub { color: #475569; margin: 0 0 18px; }
        .toolbar {
            align-items: end;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
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
        .spacer { flex: 1; }
        .status-flash {
            background: #fff;
            border: 1px solid #cbdcf5;
            border-left: 5px solid #2f9e6f;
            border-radius: 8px;
            color: #236c4f;
            margin-bottom: 16px;
            padding: 12px 14px;
        }
        .status-flash.is-error { border-left-color: #b4233f; color: #b4233f; }
        .summary {
            align-items: center;
            background: #fff;
            border: 1px solid #d8e4f5;
            border-radius: 8px;
            box-shadow: 0 10px 26px rgba(30, 64, 175, .06);
            display: flex;
            flex-wrap: wrap;
            gap: 10px 22px;
            margin-bottom: 12px;
            padding: 10px 14px;
        }
        .summary-item { align-items: baseline; display: flex; gap: 8px; }
        .summary-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .summary-value { color: #1e3a5f; font-size: 18px; font-weight: 900; }
        .summary-item.is-main .summary-value { color: #b4233f; font-size: 22px; }
        .table-shell {
            background: #fff;
            border: 1px solid #d8e4f5;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(30, 64, 175, .08);
            overflow-x: auto;
        }
        table { border-collapse: collapse; min-width: 1420px; width: 100%; }
        th, td {
            border-bottom: 1px solid #e7eef8;
            padding: 12px 13px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background: #eff6ff;
            color: #1e3a5f;
            font-size: 12px;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        tbody tr:hover { background: #f8fbff; }
        .code { color: #1d4ed8; font-weight: 900; }
        .muted { color: #64748b; font-size: 13px; }
        .wrap { max-width: 230px; overflow-wrap: anywhere; white-space: normal; }
        th.num, td.num { text-align: right; white-space: nowrap; }
        /* Toggle nguon hang: luon co dung mot the dang bat. */
        .source-tabs {
            background: #fff;
            border: 1px solid #d8e4f5;
            border-radius: 999px;
            display: inline-flex;
            gap: 4px;
            margin-bottom: 12px;
            padding: 4px;
        }
        .source-tab {
            border-radius: 999px;
            color: #1e40af;
            font-weight: 800;
            padding: 8px 18px;
            white-space: nowrap;
        }
        .source-tab:hover { background: #eff6ff; }
        .source-tab.is-on { color: #fff; }
        .source-tab.is-on:hover { opacity: .94; }
        .source-tab.tab-xuong_may.is-on { background: #7d3aa3; }
        .source-tab.tab-mua_ngoai.is-on { background: #2f5fa6; }
        td.strong { color: #1e3a5f; font-weight: 900; }
        .thumb {
            align-items: center;
            background: #e5efff;
            border: 1px solid #cbdcf5;
            border-radius: 8px;
            color: #1d4ed8;
            cursor: default;
            display: flex;
            font-family: inherit;
            font-size: 10px;
            font-weight: 800;
            height: 66px;
            justify-content: center;
            overflow: hidden;
            padding: 0;
            text-align: center;
            width: 66px;
        }
        .thumb.has-image { cursor: zoom-in; }
        .thumb.has-image:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, .16); outline: none; }
        .thumb img { height: 100%; object-fit: cover; width: 100%; }
        .image-lightbox {
            align-items: center;
            background: rgba(15, 23, 42, .72);
            cursor: zoom-out;
            display: none;
            inset: 0;
            justify-content: center;
            padding: 24px;
            position: fixed;
            z-index: 1000;
        }
        .image-lightbox.is-open { display: flex; }
        .image-lightbox img {
            background: #fff;
            border: 8px solid #fff;
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
            max-height: min(82vh, 760px);
            max-width: min(88vw, 760px);
            object-fit: contain;
        }
        .status-select {
            border-radius: 999px;
            border-style: solid;
            border-width: 1px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 900;
            min-height: 40px;
            padding: 8px 12px;
        }
        .st-chua_ve { background: #fff7d6; border-color: #e5c96a; color: #8a5a00; }
        .st-da_ve { background: #e8f7ef; border-color: #9bd9ba; color: #1f7d55; }
        .row-actions { display: flex; gap: 8px; }
        .link-action, .danger {
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 800;
            padding: 8px 10px;
        }
        .link-action { background: #eff6ff; border: 1px solid #cbdcf5; color: #1e40af; }
        .danger { background: #fff; border: 1px solid #f0b7c1; color: #b4233f; }
        .empty { color: #64748b; padding: 36px; text-align: center; }
        .pagination {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
            margin-top: 18px;
        }
        .pages { display: flex; flex-wrap: wrap; gap: 6px; }
        .page-link, .page-current {
            border-radius: 8px;
            display: inline-flex;
            font-weight: 800;
            min-width: 40px;
            padding: 10px 12px;
            place-content: center;
        }
        .page-link { background: #fff; border: 1px solid #d8e4f5; color: #1e40af; }
        .page-current { background: #2563eb; color: #fff; }
    </style>
    @include('partials.compact')
</head>
<body>
<div class="layout">
@include('partials.sidebar', ['active' => 'workshop'])
<main class="content">
    <h1>Đặt hàng</h1>
    <p class="sub">Theo dõi mã hàng đã đặt: ai đặt, đặt ngày nào, hẹn về ngày nào và đã về chưa. Danh sách tách riêng hàng đặt xưởng và hàng mua ngoài.</p>

    @if (session('status'))
        <div class="status-flash">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="status-flash is-error">{{ $errors->first() }}</div>
    @endif

    <form class="toolbar" method="GET" action="{{ route('workshop-orders.index') }}">
        <input type="hidden" name="supply_source" value="{{ $supplySource }}">
        <div class="field">
            <label for="q">Tìm mã / tên đơn / size / ghi chú</label>
            <input id="q" name="q" type="search" value="{{ $query }}" placeholder="VD: NA5414, 3xl...">
        </div>
        <div class="field">
            <label for="status">Trạng thái</label>
            <select id="status" name="status">
                <option value="">Tất cả</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="source">Tên nhà cung cấp</label>
            <input id="source" name="source" type="search" value="{{ $source }}" placeholder="VD: Huyền Trang">
        </div>
        <div class="field">
            <label for="buyer">Người mua hàng</label>
            <input id="buyer" name="buyer" type="search" value="{{ $buyer }}" placeholder="VD: chị Thanh">
        </div>
        <div class="field">
            <label for="ordered_from">Đặt từ ngày</label>
            <input id="ordered_from" name="ordered_from" type="date" value="{{ $orderedFrom }}">
        </div>
        <div class="field">
            <label for="ordered_to">Đến ngày</label>
            <input id="ordered_to" name="ordered_to" type="date" value="{{ $orderedTo }}">
        </div>
        <button class="button" type="submit">Lọc</button>
        {{-- Xoa loc nhung van o lai nguon hang dang xem. --}}
        <a class="button secondary"
           href="{{ route('workshop-orders.index', ['supply_source' => $supplySource]) }}">Xoá lọc</a>
        <div class="spacer"></div>
        <a class="button" href="{{ route('workshop-orders.create') }}">+ Thêm dòng</a>
    </form>

    {{-- Danh sách chỉ hiện một nguồn hàng; bấm để đổi qua lại. --}}
    <div class="source-tabs" role="tablist" aria-label="Nguồn hàng">
        @foreach ($supplySources as $value => $label)
            <a class="source-tab {{ $supplySource === $value ? 'is-on' : '' }} tab-{{ $value }}"
               role="tab" aria-selected="{{ $supplySource === $value ? 'true' : 'false' }}"
               href="{{ route('workshop-orders.index', array_merge(request()->except('page'), ['supply_source' => $value])) }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Tổng của toàn bộ kết quả đang lọc, không phải chỉ trang đang xem. --}}
    <div class="summary">
        <div class="summary-item">
            <span class="summary-label">Số dòng</span>
            <span class="summary-value">{{ number_format($orders->total()) }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Tổng số lượng</span>
            <span class="summary-value">{{ number_format($quantityTotal) }}</span>
        </div>
        <div class="summary-item is-main">
            <span class="summary-label">Tổng tiền</span>
            <span class="summary-value">{{ number_format($amountTotal) }}</span>
        </div>
    </div>

    <div class="table-shell">
        <table>
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Hình ảnh sản phẩm</th>
                    <th>Size</th>
                    <th class="num">Số lượng</th>
                    <th class="num">Đơn giá</th>
                    <th class="num">Thành tiền</th>
                    <th>Tên đơn</th>
                    <th>Người mua hàng ghi chú</th>
                    <th>Tên nhà cung cấp</th>
                    <th>Ngày đặt hàng</th>
                    <th>Ngày trả hàng</th>
                    <th>Người mua hàng</th>
                    <th>Hàng đã về chưa</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $item)
                    @php $product = $item->product; @endphp
                    <tr>
                        <td>
                            <div class="code">{{ $item->product_code }}</div>
                            @if ($product)
                                <div class="muted">{{ $product->name }}</div>
                            @else
                                <div class="muted">Mã không còn trong kho</div>
                            @endif
                        </td>
                        <td>
                            @if ($product && $product->image_path)
                                <button class="thumb has-image" type="button"
                                    data-full-image="{{ asset('storage/' . $product->image_path) }}"
                                    title="Bấm để xem ảnh lớn">
                                    <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $item->product_code }}">
                                </button>
                            @else
                                <span class="thumb">CHƯA CÓ ẢNH</span>
                            @endif
                        </td>
                        <td class="wrap">{{ $item->size_note ?: '—' }}</td>
                        <td class="num">{{ number_format($item->quantity) }}</td>
                        <td class="num">{{ number_format($item->unit_price) }}</td>
                        <td class="num strong">{{ number_format($item->total_amount) }}</td>
                        <td class="wrap">{{ $item->order_name ?: '—' }}</td>
                        <td class="wrap muted">{{ $item->buyer_note ?: '—' }}</td>
                        <td>{{ $item->source ?: '—' }}</td>
                        <td>{{ $item->ordered_date?->format('d/m/Y') ?: '—' }}</td>
                        <td>{{ $item->returned_date?->format('d/m/Y') ?: '—' }}</td>
                        <td>{{ $item->buyer ?: '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('workshop-orders.status', $item) }}">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="status-select st-{{ $item->status }}"
                                    onchange="this.form.submit()" aria-label="Đổi trạng thái">
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected($item->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a class="link-action" href="{{ route('workshop-orders.edit', $item) }}">Sửa</a>
                                <form method="POST" action="{{ route('workshop-orders.destroy', $item) }}"
                                    onsubmit="return confirm('Xoá dòng đặt hàng của mã {{ $item->product_code }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="danger" type="submit">Xoá</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="14">Chưa có dòng đặt hàng nào phù hợp.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($orders->hasPages())
        <nav class="pagination" aria-label="Phân trang">
            <div class="muted">
                Hiển thị {{ $orders->firstItem() }}-{{ $orders->lastItem() }} trong {{ $orders->total() }} dòng
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
</div>

<div class="image-lightbox" data-lightbox>
    <img src="" alt="Ảnh sản phẩm" data-lightbox-image>
</div>

<script>
    (function () {
        const box = document.querySelector('[data-lightbox]');
        const img = box.querySelector('[data-lightbox-image]');

        document.addEventListener('click', event => {
            const thumb = event.target.closest('[data-full-image]');
            if (thumb) {
                img.src = thumb.dataset.fullImage;
                box.classList.add('is-open');
                return;
            }
            if (event.target.closest('[data-lightbox]')) {
                box.classList.remove('is-open');
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') box.classList.remove('is-open');
        });
    })();
</script>
</body>
</html>
