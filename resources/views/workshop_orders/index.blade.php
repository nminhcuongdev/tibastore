<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đặt hàng xưởng</title>
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
            font-size: clamp(26px, 3.4vw, 36px);
            margin: 0 0 6px;
        }
        .sub { color: #704252; margin: 0 0 18px; }
        .toolbar {
            align-items: end;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
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
        .spacer { flex: 1; }
        .status-flash {
            background: #fff;
            border: 1px solid #f0c7d3;
            border-left: 5px solid #2f9e6f;
            border-radius: 8px;
            color: #236c4f;
            margin-bottom: 16px;
            padding: 12px 14px;
        }
        .status-flash.is-error { border-left-color: #b4233f; color: #b4233f; }
        .table-shell {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
            overflow-x: auto;
        }
        table { border-collapse: collapse; min-width: 1180px; width: 100%; }
        th, td {
            border-bottom: 1px solid #f7e3e9;
            padding: 12px 13px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background: #fff0f4;
            color: #81304c;
            font-size: 12px;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        tbody tr:hover { background: #fff9fb; }
        .code { color: #a13b60; font-weight: 900; }
        .muted { color: #8b6672; font-size: 13px; }
        .wrap { max-width: 230px; overflow-wrap: anywhere; white-space: normal; }
        .thumb {
            align-items: center;
            background: #f9e5ec;
            border: 1px solid #f1cbd7;
            border-radius: 8px;
            color: #a64465;
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
        .thumb.has-image:focus { border-color: #c9577d; box-shadow: 0 0 0 3px rgba(201, 87, 125, .16); outline: none; }
        .thumb img { height: 100%; object-fit: cover; width: 100%; }
        .image-lightbox {
            align-items: center;
            background: rgba(63, 39, 48, .72);
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
        .link-action { background: #fff4f7; border: 1px solid #f1cbd7; color: #8b2f4d; }
        .danger { background: #fff; border: 1px solid #f0b7c1; color: #b4233f; }
        .empty { color: #8b6672; padding: 36px; text-align: center; }
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
        .page-link { background: #fff; border: 1px solid #f0d3dc; color: #8b2f4d; }
        .page-current { background: #be476f; color: #fff; }
    </style>
    @include('partials.compact')
</head>
<body>
<div class="layout">
@include('partials.sidebar', ['active' => 'workshop'])
<main class="content">
    <h1>Đặt hàng xưởng</h1>
    <p class="sub">Theo dõi mã hàng đã đặt xưởng: ai đặt, đặt ngày nào, hẹn trả ngày nào và xưởng đã trả hàng chưa.</p>

    @if (session('status'))
        <div class="status-flash">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="status-flash is-error">{{ $errors->first() }}</div>
    @endif

    <form class="toolbar" method="GET" action="{{ route('workshop-orders.index') }}">
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
            <label for="source">Nguồn đặt</label>
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
        <a class="button secondary" href="{{ route('workshop-orders.index') }}">Xoá lọc</a>
        <div class="spacer"></div>
        <a class="button" href="{{ route('workshop-orders.create') }}">+ Thêm dòng</a>
    </form>

    <div class="table-shell">
        <table>
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Hình ảnh sản phẩm</th>
                    <th>Size</th>
                    <th>Tên đơn</th>
                    <th>Người mua hàng ghi chú</th>
                    <th>Nguồn đặt</th>
                    <th>Ngày đặt hàng</th>
                    <th>Ngày trả hàng</th>
                    <th>Người mua hàng</th>
                    <th>Xưởng đã trả chưa</th>
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
                                    onsubmit="return confirm('Xoá dòng đặt xưởng của mã {{ $item->product_code }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="danger" type="submit">Xoá</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="11">Chưa có dòng đặt xưởng nào phù hợp.</td>
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
