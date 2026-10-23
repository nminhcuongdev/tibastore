<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chi tiết mã hàng {{ $product->code }}</title>
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
        .topbar {
            align-items: center;
            background: #fff;
            border-bottom: 1px solid #dce7f7;
            display: flex;
            gap: 20px;
            justify-content: space-between;
            padding: 18px clamp(18px, 5vw, 56px);
        }
        .brand {
            color: #1d4ed8;
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
            border: 1px solid #b9cfee;
            border-radius: 999px;
            color: #1e40af;
            cursor: pointer;
            font-weight: 700;
            padding: 10px 16px;
        }
        .nav a.active {
            background: #2563eb;
            color: #fff;
        }
        .content { padding: 28px clamp(18px, 5vw, 56px) 56px; }
        .status {
            background: #fff;
            border: 1px solid #cbdcf5;
            border-left: 5px solid #2f9e6f;
            border-radius: 8px;
            color: #236c4f;
            margin-bottom: 18px;
            padding: 12px 14px;
        }
        .status.is-off { border-left-color: #64748b; color: #475569; }
        .off-badge {
            background: #e8eef5;
            border-radius: 999px;
            color: #64748b;
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 13px;
            font-weight: 900;
            padding: 4px 11px;
            vertical-align: middle;
        }
        .status.is-error {
            border-left-color: #b4233f;
            color: #b4233f;
        }
        .summary {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 20px;
        }
        .metric,
        .table-shell {
            background: #fff;
            border: 1px solid #d8e4f5;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(30, 64, 175, .08);
        }
        .metric { padding: 18px; }
        .metric-label {
            color: #64748b;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .metric-value {
            color: #172f50;
            font-size: 24px;
            font-weight: 900;
        }
        .table-shell { overflow-x: auto; }
        table {
            border-collapse: collapse;
            min-width: 1180px;
            width: 100%;
        }
        th,
        td {
            border-bottom: 1px solid #e7eef8;
            padding: 15px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background: #eff6ff;
            color: #1e3a5f;
            font-size: 13px;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        th a {
            align-items: center;
            display: inline-flex;
            gap: 6px;
        }
        tbody tr.clickable { cursor: pointer; }
        tbody tr.clickable:hover { background: #f8fbff; }
        .thumb {
            align-items: center;
            background: #e5efff;
            border: 1px solid #cbdcf5;
            border-radius: 8px;
            color: #1d4ed8;
            display: flex;
            font-size: 12px;
            font-weight: 800;
            height: 70px;
            justify-content: center;
            overflow: hidden;
            width: 70px;
        }
        .thumb img {
            height: 100%;
            object-fit: cover;
            width: 100%;
        }
        /* Hang da ve kho nhung chua kiem: da cong vao ton, chi nhac kiem. */
        .pending-check {
            background: #fff7d6;
            border-radius: 999px;
            color: #8a5a00;
            display: inline-block;
            font-size: 11px;
            font-weight: 800;
            margin-top: 4px;
            padding: 2px 8px;
            white-space: nowrap;
        }
        .code {
            color: #1d4ed8;
            font-weight: 900;
        }
        .name {
            color: #1e293b;
            font-weight: 800;
        }
        .muted {
            color: #64748b;
            font-size: 13px;
        }
        .row-actions {
            display: flex;
            gap: 8px;
        }
        .page-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
            margin: 0 0 18px;
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
            background: #eff6ff;
            border: 1px solid #cbdcf5;
            color: #1e40af;
        }
        .danger {
            background: #fff;
            border: 1px solid #f0b7c1;
            color: #b4233f;
        }
        .empty {
            color: #64748b;
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
            border: 1px solid #d8e4f5;
            color: #1e40af;
        }
        .page-current {
            background: #2563eb;
            color: #fff;
        }
        @media (max-width: 760px) {
            .topbar {
                align-items: stretch;
                flex-direction: column;
            }
            .summary { grid-template-columns: 1fr; }
            .nav a, .logout { justify-content: center; width: 100%; }
        }
    </style>
    @include('partials.compact')
</head>
<body>
<div class="layout">
@include('partials.sidebar', ['active' => 'products'])
<div class="page">

<main class="content">
        <h1>
            {{ $product->code }} - {{ $product->name }}
            @if ($product->isDiscontinued())
                <span class="off-badge">Đã ngừng dùng</span>
            @endif
        </h1>
        @if ($product->isDiscontinued())
            <div class="status is-off">
                Mã này đã ngừng dùng từ {{ $product->discontinued_at?->format('d/m/Y') }}: không hiện ở kho,
                không chọn được khi lên đơn. Đơn hàng cũ vẫn giữ nguyên.
            </div>
        @endif
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="status is-error">{{ session('error') }}</div>
        @endif

        <div class="summary">
            <div class="metric">
                <div class="metric-label">Tổng tồn</div>
                <div class="metric-value">{{ number_format($totalQuantity) }}</div>
            </div>
            <div class="metric">
                <div class="metric-label">Số size</div>
                <div class="metric-value">{{ number_format($variants->total()) }}</div>
            </div>
            <div class="metric">
                <div class="metric-label">Mã hàng</div>
                <div class="metric-value">{{ $product->code }}</div>
            </div>
        </div>

        <div class="page-actions">
            <a class="link-action" href="{{ route('products.create', ['copy_from' => $product->id]) }}">Thêm size</a>
            @if ($product->isDiscontinued())
                <form method="POST" action="{{ route('products.discontinued', $product) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="resume">
                    <button class="link-action" type="submit" style="font-family: inherit;">Dùng lại mã này</button>
                </form>
            @else
                {{-- Ma da len don thi khong xoa duoc; ngung dung de cat khoi kho. --}}
                <form method="POST" action="{{ route('products.discontinued', $product) }}"
                    onsubmit="return confirm('Ngừng dùng mã {{ $product->code }}? Mã sẽ không hiện ở kho và không chọn được khi lên đơn, đơn cũ vẫn giữ nguyên.')">
                    @csrf
                    @method('PATCH')
                    <button class="link-action" type="submit" style="font-family: inherit;">Ngừng dùng mã này</button>
                </form>
            @endif
            <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Xóa mã hàng này và toàn bộ size khỏi kho?')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="delete_scope" value="code">
                <button class="danger" type="submit">Xóa mã hàng</button>
            </form>
        </div>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Hình ảnh</th>
                        <th>Mã sản phẩm</th>
                        <th>Tên sản phẩm</th>
                        <th>
                            <a href="{{ route('products.show', array_merge(['product' => $product], request()->except('page'), ['sort' => 'quantity', 'direction' => $sort === 'quantity' && $direction === 'asc' ? 'desc' : 'asc'])) }}">
                                Số lượng {{ $sort === 'quantity' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}
                            </a>
                        </th>
                        <th>Vải</th>
                        <th>Danh mục</th>
                        <th>Ngày dự kiến nhận</th>
                        <th>SL nhận dự kiến</th>
                        <th>
                            <a href="{{ route('products.show', array_merge(['product' => $product], request()->except('page'), ['sort' => 'size', 'direction' => $sort === 'size' && $direction === 'asc' ? 'desc' : 'asc'])) }}">
                                Size {{ $sort === 'size' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}
                            </a>
                        </th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($variants as $variant)
                        <tr class="clickable" data-href="{{ route('products.track', $variant) }}">
                            <td>
                                <div class="thumb">
                                    @if ($variant->image_path)
                                        <img src="{{ asset('storage/' . $variant->image_path) }}" alt="{{ $variant->name }}">
                                    @else
                                        CHƯA CÓ ẢNH
                                    @endif
                                </div>
                            </td>
                            <td class="code">{{ $variant->code }}</td>
                            <td>
                                <div class="name">{{ $variant->name }}</div>
                                <div class="muted">Cập nhật: {{ $variant->updated_at?->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                {{ number_format($variant->stock_quantity) }}
                                @if (! empty($pendingInspection[$variant->id]))
                                    <div class="pending-check" title="Đã về kho, chưa kiểm">
                                        {{ number_format($pendingInspection[$variant->id]) }} chờ kiểm
                                    </div>
                                @endif
                            </td>
                            <td>{{ $variant->fabric }}</td>
                            <td>{{ $variant->category ?: 'N/A' }}</td>
                            <td>
                                @forelse ($variant->expectedReceipts->whereNull('received_at') as $receipt)
                                    <div>{{ $receipt->expected_receive_date?->format('d/m/Y') }}</div>
                                @empty
                                    N/A
                                @endforelse
                            </td>
                            <td>
                                @forelse ($variant->expectedReceipts->whereNull('received_at') as $receipt)
                                    <div>{{ number_format($receipt->expected_receive_quantity) }}</div>
                                @empty
                                    0
                                @endforelse
                            </td>
                            <td>{{ $variant->size }}</td>
                            <td>
                                <div class="row-actions">
                                    <a class="link-action" href="{{ route('products.track', $variant) }}">Theo dõi</a>
                                    <a class="link-action" href="{{ route('products.edit', $variant) }}">Sửa</a>
                                    <form method="POST" action="{{ route('products.destroy', $variant) }}" onsubmit="return confirm('Xóa sản phẩm này khỏi kho?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger" type="submit">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty" colspan="10">Chưa có sản phẩm phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($variants->hasPages())
            <nav class="pagination" aria-label="Phân trang">
                <div class="muted">
                    Hiển thị {{ $variants->firstItem() }}-{{ $variants->lastItem() }} trong {{ $variants->total() }} sản phẩm
                </div>
                <div class="pages">
                    @if ($variants->onFirstPage())
                        <span class="page-link">Trước</span>
                    @else
                        <a class="page-link" href="{{ $variants->previousPageUrl() }}">Trước</a>
                    @endif

                    @for ($page = 1; $page <= $variants->lastPage(); $page++)
                        @if ($page === $variants->currentPage())
                            <span class="page-current">{{ $page }}</span>
                        @else
                            <a class="page-link" href="{{ $variants->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor

                    @if ($variants->hasMorePages())
                        <a class="page-link" href="{{ $variants->nextPageUrl() }}">Sau</a>
                    @else
                        <span class="page-link">Sau</span>
                    @endif
                </div>
            </nav>
        @endif
    </main>
    @include('orders.reminders-popup')
    <script>
        document.querySelectorAll('tr[data-href]').forEach(row => {
            row.addEventListener('click', event => {
                if (event.target.closest('a, button, form')) {
                    return;
                }

                window.location.href = row.dataset.href;
            });
        });
    </script>
    </div>
</div>
</body>
</html>
