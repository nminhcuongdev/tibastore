<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lí kho thời trang</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #fff7f9;
            color: #3f2730;
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

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
            letter-spacing: .2px;
        }

        .logout {
            background: transparent;
            border: 1px solid #e8b8c8;
            border-radius: 999px;
            color: #8b2f4d;
            cursor: pointer;
            font-weight: 700;
            padding: 10px 16px;
        }

        .nav {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav a {
            background: #fff;
            border: 1px solid #e8b8c8;
            border-radius: 999px;
            color: #8b2f4d;
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
            min-height: 260px;
            padding: 50px clamp(18px, 5vw, 56px);
        }

        .hero h1 {
            color: #6f253f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(34px, 5vw, 58px);
            line-height: 1.04;
            margin: 0 0 14px;
            max-width: 760px;
        }

        .hero p {
            color: #704252;
            font-size: 17px;
            line-height: 1.6;
            margin: 0;
            max-width: 680px;
        }

        .content {
            padding: 28px clamp(18px, 5vw, 56px) 56px;
        }

        .toolbar {
            align-items: end;
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr auto;
            margin-bottom: 18px;
        }

        .search {
            display: grid;
            gap: 8px;
            max-width: 520px;
        }

        label {
            color: #7a344c;
            font-size: 13px;
            font-weight: 700;
        }

        input,
        select {
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 8px;
            color: #3f2730;
            font-size: 15px;
            min-height: 44px;
            padding: 10px 13px;
            width: 100%;
        }

        input:focus,
        select:focus {
            border-color: #c9577d;
            box-shadow: 0 0 0 3px rgba(201, 87, 125, .16);
            outline: none;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
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
            gap: 8px;
            min-height: 44px;
            padding: 10px 16px;
        }

        .button.secondary {
            background: #fff;
            border: 1px solid #ebc5d2;
            color: #8b2f4d;
        }

        .status {
            background: #fff;
            border: 1px solid #f0c7d3;
            border-left: 5px solid #c9577d;
            border-radius: 8px;
            color: #693143;
            margin-bottom: 18px;
            padding: 12px 14px;
        }

        .table-shell {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            overflow-x: auto;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
        }

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

        tbody tr:hover {
            background: #fff9fb;
        }

        .thumb {
            align-items: center;
            background: #f9e5ec;
            border: 1px solid #f1cbd7;
            border-radius: 8px;
            color: #a64465;
            cursor: default;
            display: flex;
            font-family: inherit;
            font-size: 12px;
            font-weight: 800;
            height: 70px;
            justify-content: center;
            overflow: hidden;
            padding: 0;
            position: relative;
            width: 70px;
        }

        .thumb.has-image {
            cursor: zoom-in;
        }

        .thumb img {
            height: 100%;
            object-fit: cover;
            transition: transform .18s ease;
            width: 100%;
        }

        .thumb.has-image:hover,
        .thumb.has-image:focus {
            border-color: #c9577d;
            box-shadow: 0 10px 24px rgba(117, 44, 69, .18);
            outline: none;
            overflow: visible;
            z-index: 20;
        }

        .thumb.has-image:hover img,
        .thumb.has-image:focus img {
            border-radius: 8px;
            transform: scale(2.4);
        }

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

        .image-lightbox.is-open {
            display: flex;
        }

        .image-lightbox img {
            background: #fff;
            border: 8px solid #fff;
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
            max-height: min(82vh, 760px);
            max-width: min(88vw, 760px);
            object-fit: contain;
        }

        .code {
            color: #a13b60;
            font-weight: 800;
        }

        .code-line {
            align-items: center;
            display: flex;
            gap: 8px;
        }

        .copy-code {
            background: #fff;
            border: 1px solid #f1cbd7;
            border-radius: 8px;
            color: #8b2f4d;
            cursor: pointer;
            font-size: 12px;
            font-weight: 800;
            min-height: 28px;
            padding: 4px 8px;
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

        .groups-summary {
            align-items: center;
            color: #8b6672;
            display: flex;
            flex-wrap: wrap;
            font-size: 14px;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .category-group {
            margin-bottom: 24px;
        }

        .category-head {
            align-items: center;
            background: #fff0f4;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            color: #81304c;
            cursor: pointer;
            display: flex;
            font-family: inherit;
            font-size: 16px;
            font-weight: 800;
            gap: 12px;
            margin-bottom: 10px;
            padding: 14px 16px;
            text-align: left;
            width: 100%;
        }

        .category-head:hover {
            background: #ffe6ee;
        }

        .category-caret {
            font-size: 13px;
            transition: transform .18s ease;
        }

        .category-group.is-collapsed .category-caret {
            transform: rotate(-90deg);
        }

        .category-group.is-collapsed [data-category-body] {
            display: none;
        }

        .category-name {
            flex: 1;
        }

        .category-meta {
            color: #8b6672;
            font-size: 13px;
            font-weight: 700;
            text-transform: none;
        }

        @media (max-width: 760px) {
            .topbar,
            .toolbar {
                align-items: stretch;
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
            }

            .actions {
                width: 100%;
            }

            .button,
            .logout {
                justify-content: center;
                width: 100%;
            }
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
        <h1>Quản lí kho hàng thời trang</h1>
        <p>Theo dõi mã sản phẩm, chất liệu vải, size, số lượng tồn và giá nhập trong một không gian mềm mại như một cửa hàng boutique.</p>
    </section>

    <main class="content">
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <div class="toolbar">
            <form class="search" method="GET" action="{{ route('products.index') }}">
                <label for="q">Tìm kiếm theo mã hoặc tên sản phẩm</label>
                <input id="q" name="q" type="search" value="{{ $query }}" placeholder="VD: VAY001, áo lụa...">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
            </form>

            <div class="actions">
                <a class="button secondary" href="{{ route('products.index') }}">Làm mới</a>
                <a class="button" href="{{ route('products.create') }}">+ Thêm sản phẩm</a>
            </div>
        </div>

        <div class="groups-summary">
            <span>{{ number_format($totalProducts) }} mã hàng trong {{ number_format($productGroups->count()) }} danh mục</span>
            @if ($productGroups->isNotEmpty())
                <button type="button" class="button secondary" data-bulk-toggle>Sổ hết tất cả</button>
            @endif
        </div>

        @forelse ($productGroups as $category => $items)
            <section class="category-group is-collapsed" data-category-group>
                <button type="button" class="category-head" data-category-toggle aria-expanded="false">
                    <span class="category-caret" aria-hidden="true">▾</span>
                    <span class="category-name">{{ $category }}</span>
                    <span class="category-meta">{{ number_format($items->count()) }} mã · {{ number_format($items->sum('total_stock_quantity')) }} sp tồn</span>
                </button>
                <div class="table-shell" data-category-body>
                    <table>
                        <thead>
                            <tr>
                                <th>Hình ảnh</th>
                                <th>
                                    <a href="{{ route('products.index', array_merge(request()->except('page'), ['sort' => 'code', 'direction' => $sort === 'code' && $direction === 'asc' ? 'desc' : 'asc'])) }}">
                                        Mã sản phẩm {{ $sort === 'code' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ route('products.index', array_merge(request()->except('page'), ['sort' => 'name', 'direction' => $sort === 'name' && $direction === 'asc' ? 'desc' : 'asc'])) }}">
                                        Tên sản phẩm {{ $sort === 'name' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ route('products.index', array_merge(request()->except('page'), ['sort' => 'quantity', 'direction' => $sort === 'quantity' && $direction === 'asc' ? 'desc' : 'asc'])) }}">
                                        Số lượng {{ $sort === 'quantity' ? ($direction === 'asc' ? 'ASC' : 'DESC') : '' }}
                                    </a>
                                </th>
                                <th>Vải</th>
                                <th>Ngày dự kiến nhận</th>
                                <th>SL nhận dự kiến</th>
                                <th>Số size</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $product)
                                <tr>
                                    <td>
                                        @if ($product->image_path)
                                            <button class="thumb has-image" type="button" data-full-image="{{ asset('storage/' . $product->image_path) }}" aria-label="Phóng to ảnh {{ $product->name }}">
                                                <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}">
                                            </button>
                                        @else
                                            <div class="thumb">
                                                CHƯA CÓ ẢNH
                                            </div>
                                        @endif
                                    </td>
                                    <td class="code">
                                        <div class="code-line">
                                            <a href="{{ route('products.show', $product) }}">{{ $product->code }}</a>
                                            <button class="copy-code" type="button" data-copy-code="{{ $product->code }}">copy</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="name">
                                            <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                                        </div>
                                        <div class="muted">Cập nhật: {{ $product->updated_at?->format('d/m/Y') }}</div>
                                    </td>
                                    <td>{{ number_format($product->total_stock_quantity) }}</td>
                                    <td>{{ $product->fabric }}</td>
                                    <td>{{ $product->expected_receive_date ? \Illuminate\Support\Carbon::parse($product->expected_receive_date)->format('d/m/Y') : 'N/A' }}</td>
                                    <td>{{ number_format($product->total_expected_receive_quantity ?? 0) }}</td>
                                    <td>{{ number_format($product->size_count) }}</td>
                                    <td>
                                        <div class="row-actions">
                                            <a class="link-action" href="{{ route('products.show', $product) }}">Chi tiết</a>
                                            <a class="link-action" href="{{ route('products.create', ['copy_from' => $product->id]) }}">Thêm size</a>
                                            <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Xóa mã hàng này và toàn bộ size khỏi kho?')">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="delete_scope" value="code">
                                                <button class="danger" type="submit">Xóa mã</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="table-shell">
                <div class="empty">Chưa có sản phẩm phù hợp.</div>
            </div>
        @endforelse
    </main>
    <div class="image-lightbox" data-image-lightbox aria-hidden="true">
        <img data-lightbox-image src="" alt="">
    </div>
    @include('orders.reminders-popup')
    <script>
        const lightbox = document.querySelector('[data-image-lightbox]');
        const lightboxImage = document.querySelector('[data-lightbox-image]');

        document.querySelectorAll('[data-full-image]').forEach(button => {
            button.addEventListener('click', () => {
                lightboxImage.src = button.dataset.fullImage;
                lightboxImage.alt = button.querySelector('img')?.alt || 'Ảnh sản phẩm';
                lightbox.classList.add('is-open');
                lightbox.setAttribute('aria-hidden', 'false');
            });
        });

        function closeLightbox() {
            lightbox.classList.remove('is-open');
            lightbox.setAttribute('aria-hidden', 'true');
            lightboxImage.src = '';
        }

        lightbox.addEventListener('click', closeLightbox);

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && lightbox.classList.contains('is-open')) {
                closeLightbox();
            }
        });

        const categoryGroups = Array.from(document.querySelectorAll('[data-category-group]'));
        const bulkToggle = document.querySelector('[data-bulk-toggle]');

        function anyGroupExpanded() {
            return categoryGroups.some(group => ! group.classList.contains('is-collapsed'));
        }

        function updateBulkToggleLabel() {
            if (! bulkToggle) {
                return;
            }

            bulkToggle.textContent = anyGroupExpanded() ? 'Gom hết tất cả' : 'Sổ hết tất cả';
        }

        function setGroupCollapsed(group, collapsed) {
            group.classList.toggle('is-collapsed', collapsed);
            const head = group.querySelector('[data-category-toggle]');

            if (head) {
                head.setAttribute('aria-expanded', String(! collapsed));
            }
        }

        document.querySelectorAll('[data-category-toggle]').forEach(button => {
            button.addEventListener('click', () => {
                const group = button.closest('[data-category-group]');
                setGroupCollapsed(group, ! group.classList.contains('is-collapsed'));
                updateBulkToggleLabel();
            });
        });

        if (bulkToggle) {
            bulkToggle.addEventListener('click', () => {
                const collapseAll = anyGroupExpanded();
                categoryGroups.forEach(group => setGroupCollapsed(group, collapseAll));
                updateBulkToggleLabel();
            });
        }

        updateBulkToggleLabel();

        document.querySelectorAll('[data-copy-code]').forEach(button => {
            button.addEventListener('click', async event => {
                event.preventDefault();
                event.stopPropagation();

                const code = button.dataset.copyCode;

                try {
                    await navigator.clipboard.writeText(code);
                    button.textContent = 'đã copy';
                    setTimeout(() => button.textContent = 'copy', 1200);
                } catch (error) {
                    const input = document.createElement('input');
                    input.value = code;
                    document.body.appendChild(input);
                    input.select();
                    document.execCommand('copy');
                    input.remove();
                    button.textContent = 'đã copy';
                    setTimeout(() => button.textContent = 'copy', 1200);
                }
            });
        });
    </script>
</body>
</html>



