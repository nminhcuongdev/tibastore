<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chi tiết mã hàng {{ $product->code }}</title>
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
                linear-gradient(115deg, rgba(255, 255, 255, .92), rgba(255, 235, 242, .78)),
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
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 20px;
        }
        .metric,
        .table-shell {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
        }
        .metric { padding: 18px; }
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
        .table-shell { overflow-x: auto; }
        table {
            border-collapse: collapse;
            min-width: 1180px;
            width: 100%;
        }
        th,
        td {
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
        th a {
            align-items: center;
            display: inline-flex;
            gap: 6px;
        }
        tbody tr.clickable { cursor: pointer; }
        tbody tr.clickable:hover { background: #fff9fb; }
        .thumb {
            align-items: center;
            background: #f9e5ec;
            border: 1px solid #f1cbd7;
            border-radius: 8px;
            color: #a64465;
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
            background: #fff4f7;
            border: 1px solid #f1cbd7;
            color: #8b2f4d;
        }
        .danger {
            background: #fff;
            border: 1px solid #f0b7c1;
            color: #b4233f;
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
            .topbar {
                align-items: stretch;
                flex-direction: column;
            }
            .summary { grid-template-columns: 1fr; }
            .nav a, .logout { justify-content: center; width: 100%; }
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
            @if (auth()->user()?->isAdmin())
                <a href="{{ route('users.index') }}">Người dùng</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout" type="submit">Đăng xuất</button>
            </form>
        </div>
    </header>

    <section class="hero">
        <h1>{{ $product->code }} - {{ $product->name }}</h1>
        <p>Danh sách các size của cùng mã hàng. Bấm vào từng dòng để xem theo dõi tồn kho và đơn hàng của size đó.</p>
    </section>

    <main class="content">
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
                        <th>Ngày dự kiến nhận</th>
                        <th>SL nhận dự kiến</th>
                        <th>
                            <a href="{{ route('products.show', array_merge(['product' => $product], request()->except('page'), ['sort' => 'size', 'direction' => $sort === 'size' && $direction === 'asc' ? 'desc' : 'asc'])) }}">
                                Size {{ $sort === 'size' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('products.show', array_merge(['product' => $product], request()->except('page'), ['sort' => 'price', 'direction' => $sort === 'price' && $direction === 'asc' ? 'desc' : 'asc'])) }}">
                                Giá nhập {{ $sort === 'price' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}
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
                            <td>{{ number_format($variant->stock_quantity) }}</td>
                            <td>{{ $variant->fabric }}</td>
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
                            <td>{{ number_format((float) $variant->import_price, 0, ',', '.') }} VND</td>
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
</body>
</html>
