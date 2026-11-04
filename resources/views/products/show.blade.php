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
            color: #1e40af;
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
            background: #1d4ed8;
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
            color: #1e40af;
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
            color: #1e40af;
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
            background: #1d4ed8;
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
            <span class="click-copy" data-copy-value="{{ $product->code }}" role="button" tabindex="0" title="Bấm để sao chép">{{ $product->code }}</span> - {{ $product->name }}
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
                <div class="metric-label">Tổng số hàng</div>
                <div class="metric-value" data-stock-metric-total data-rented="{{ $totalRented }}">{{ number_format($totalQuantity + $totalRented) }}</div>
            </div>
            <div class="metric">
                <div class="metric-label">Đang cho thuê</div>
                <div class="metric-value">{{ number_format($totalRented) }}</div>
            </div>
            <div class="metric">
                <div class="metric-label">Còn trong kho</div>
                <div class="metric-value" data-stock-metric-quantity>{{ number_format($totalQuantity) }}</div>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin:12px 0 20px;">
            @include('partials.personal-history', ['historySection' => 'products'])
        </div>

        <p class="muted">Tổng số hàng = còn trong kho + đang cho thuê. Chưa cộng hàng nhập dự kiến; hàng mất/hỏng đã kiểm không nằm trong số đang cho thuê.</p>
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

        @include('products.inline-stock')

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
        <section id="purchase-history" style="margin-top:28px">
            <h2>Lịch sử mua</h2>
            <p>Gồm nhập hàng và điều chỉnh tăng/giảm trực tiếp tại Kho.
            </p>
            @error('undo')<div class="status is-error" role="alert">{{ $message }}</div>@enderror
            <div class="table-shell" style="overflow-x:auto">
                <table>
                    <thead><tr><th>Ngày ghi nhận</th><th>Nguồn mua / Nhà cung cấp</th><th>Tên đơn</th><th>Size · Thay đổi số lượng</th><th>Người thực hiện</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                    <tbody>
                    @forelse ($purchaseHistory as $entry)
                        <tr>
                            <td>{{ $entry->transferred_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</td>
                            <td>{{ $entry->supply_source === 'direct_stock' ? 'Nhập trực tiếp tại Kho' : (\App\Models\WorkshopOrder::supplySources()[$entry->supply_source] ?? $entry->supply_source) }}<br>{{ $entry->supplier }}</td>
                            <td>{{ $entry->order_name ?: '—' }}</td>
                            <td>@foreach ($entry->lines as $line)<div>{{ $line['size'] }}: <strong>{{ $line['quantity'] > 0 ? '+' : '' }}{{ $line['quantity'] }}</strong>@if (isset($line['previous_quantity'])) <small>({{ $line['previous_quantity'] }} → {{ $line['new_quantity'] }})</small>@endif</div>@endforeach</td>
                            <td>{{ $entry->user?->name ?? '—' }}</td>
                            <td>@if ($entry->undone_at)Đã hoàn tác<br>{{ $entry->undone_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}@else {{ $entry->supply_source === 'direct_stock' ? 'Đã ghi nhận' : 'Đã nhập kho' }} @endif</td>
                            <td>
                                @if ($entry->workshop_order_id)<a class="link-action" href="{{ route('workshop-orders.index', ['focus' => $entry->workshop_order_id]) }}">Xem đặt hàng</a>@endif
                                @if ($entry->undone_at)
                                    <form method="POST" action="{{ route('workshop-orders.delete-stock-history', $entry) }}" onsubmit="return confirm('Xóa dòng lịch sử đã hoàn tác này?');" style="margin-top:8px">
                                        @csrf @method('DELETE')
                                        <button class="link-action danger" type="submit">Xóa dòng</button>
                                    </form>
                                @endif
                                @if (!$entry->undone_at && $entry->can_undo)
                                    <form method="POST" action="{{ route('workshop-orders.undo-stock', $entry) }}" onsubmit="return confirm('Hoàn tác lần ghi nhận này? Kho sẽ đảo lại đúng phần tăng hoặc giảm của từng size.');" style="margin-top:8px">
                                        @csrf<button class="link-action danger" type="submit">Hoàn tác</button>
                                    </form>
                                @elseif (!$entry->undone_at)
                                    <div>Chưa đủ dữ liệu hoàn tác tự động</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Chưa có lịch sử nhập hàng cho mã này.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $purchaseHistory->fragment('purchase-history')->links() }}
        </section>
        @include('products.edit-history')
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
