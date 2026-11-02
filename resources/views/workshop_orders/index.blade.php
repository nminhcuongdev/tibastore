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
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .16);
            outline: none;
        }
        .button {
            align-items: center;
            background: #1d4ed8;
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
        .code { color: #1e40af; font-weight: 900; }
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
            color: #1e40af;
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
        .thumb.has-image:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .16); outline: none; }
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
        .st-da_od { background: #fce4ef; border-color: #e8a4c3; color: #96315e; }
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
        .page-current { background: #1d4ed8; color: #fff; }
    </style>
    @include('partials.compact')
@include('workshop_orders.inline-edit-styles')
@include('workshop_orders.product-picker-styles')
</head>
<body>
<div class="layout">
@include('partials.sidebar', ['active' => 'workshop'])
<main class="content">

                <style>
                    tr[data-edit-row].is-duplicate > td { background:#ffe3e3!important;border-top:2px solid #e24a4a;border-bottom:2px solid #e24a4a; }
                    [data-duplicate-warning] { display:block;max-width:260px;margin-top:8px;color:#a51220;font-size:12px;font-weight:700;white-space:normal; }
                    [data-duplicate-warning][hidden] { display:none; }
                </style>
    <h1>Đặt hàng</h1>
    @include('partials.workshop-duplicate-notice')
    <p class="sub">Theo dõi mã hàng đã đặt: ai đặt, đặt ngày nào, hẹn về ngày nào và đã về chưa. Danh sách tách riêng hàng đặt xưởng và hàng mua ngoài.</p>

    @if (session('status'))
        <div class="status-flash">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="status-flash is-error">{{ $errors->first() }}</div>
    @endif

    <form class="toolbar" method="GET" action="{{ route('workshop-orders.index') }}">
        <input type="hidden" name="supply_source" value="{{ $supplySource }}">
        @if ($sort)
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
        @endif
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
        <button class="button secondary" type="button" data-undo-edit title="Hoàn tác tối đa 5 thao tác gần nhất của tài khoản bạn">↶ Hoàn tác</button>
        <button class="button secondary" type="button" data-redo-edit title="Làm lại thao tác vừa hoàn tác của tài khoản bạn">↷ Làm lại</button>
        <span data-undo-state role="status"></span>
        @include('partials.stock-receipt-reminders')
        <div class="spacer"></div>
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
            <span class="summary-value" data-summary-quantity="{{ $quantityTotal }}">{{ number_format($quantityTotal) }}</span>
        </div>
        <div class="summary-item is-main">
            <span class="summary-label">Tổng tiền</span>
            <span class="summary-value" data-summary-amount="{{ $amountTotal }}">{{ number_format($amountTotal) }}</span>
        </div>
    </div>

    @if ($focusedOrder)
        <p role="status">Đang xem dòng đặt hàng {{ $focusedOrder->product_code }} — {{ $focusedOrder->order_name }}.
            <a href="{{ route('workshop-orders.index', ['supply_source' => $supplySource]) }}">Xem tất cả đặt hàng</a>
        </p>
    @endif
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
                    @foreach (['ordered_date' => 'Ngày đặt hàng', 'returned_date' => 'Ngày trả hàng'] as $column => $label)
                        @php
                            $activeSort = $sort === $column;
                            $nextDirection = $activeSort && $direction === 'desc' ? 'asc' : 'desc';
                        @endphp
                        <th aria-sort="{{ $activeSort ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a style="color:inherit;text-decoration:none;white-space:nowrap" href="{{ request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDirection, 'page' => null]) }}"
                               title="{{ $nextDirection === 'desc' ? 'Sắp xếp gần nhất trước' : 'Sắp xếp xa nhất trước' }}">
                                {{ $label }} <span aria-hidden="true">{{ $activeSort ? ($direction === 'desc' ? '↓' : '↑') : '↕' }}</span>
                            </a>
                        </th>
                    @endforeach
                    <th>Người mua hàng</th>
                    <th>Hàng đã về chưa</th>
                    <th>Thao tác</th>
                    <th>Xóa dòng</th>
                    <th>Đẩy lên kho</th>
                </tr>
            </thead>
            <caption style="caption-side:top;text-align:left;padding:12px;color:#7386a0;font-size:12px">Các thay đổi được tự động lưu. Thành tiền tự tính theo số lượng và đơn giá.</caption>
            <tbody data-workshop-rows>

                @forelse ($orders as $item)
                    @php $product = $item->product; @endphp
                    <tr id="workshop-row-{{ $item->id }}" data-edit-row data-order-id="{{ $item->id }}" data-customer-order-id="{{ $item->customer_order_id }}" data-order-url="{{ $item->customer_order_url }}" data-warehouse-code="{{ $item->product_code }}" data-warehouse-url="{{ $product ? route('products.show', $product) : '' }}" data-size-quantities="{{ json_encode($item->purchase_plan['lines'] ?? $item->size_quantities ?? []) }}" data-managed-plan="{{ $item->purchase_plan ? '1' : '0' }}" data-stocked="{{ $item->stocked_at ? '1' : '0' }}" data-stock-url="{{ route('workshop-orders.stock', $item) }}" data-custom-image="{{ $item->image_path ? $item->image_url : '' }}" class="{{ isset($duplicateRows[$item->id]) ? 'is-duplicate ' : '' }}{{ $item->hasArrived() ? 'is-arrived' : ($item->status === \App\Models\WorkshopOrder::STATUS_ORDERED ? 'is-ordered' : '') }}" data-quantity="{{ $item->quantity }}" data-total="{{ $item->total_amount }}">
                        <td>
                            <input form="workshop-edit-{{ $item->id }}" name="product_code" value="{{ $item->product_code }}" required maxlength="50" aria-label="Mã hàng">
                            @if ($product)
                                <div class="muted" data-product-label>{{ $product->name }}</div>
                            @else
                                <div class="muted" data-product-label>{{ $item->product_code === \App\Models\WorkshopOrder::NO_PRODUCT_CODE ? 'Chưa tạo mã' : 'Mã không còn trong kho' }}</div>
                            @endif
                            <span data-duplicate-warning role="alert" @if (!isset($duplicateRows[$item->id])) hidden @endif>
                                @if (isset($duplicateRows[$item->id]))
                                    ⚠ Nghi trùng Tên đơn và Mã hàng với dòng #{{ implode(', #', $duplicateRows[$item->id]) }}. Kiểm tra để tránh od hai lần.
                                @endif
                            </span>
                        </td>
                        <td data-product-image>
                            @if ($item->image_url)
                                <button class="thumb has-image" type="button"
                                    data-full-image="{{ $item->image_url }}"
                                    title="Bấm để xem ảnh lớn">
                                    <img src="{{ $item->image_url }}" alt="{{ $item->product_code }}">
                                </button>
                            @else
                                <span class="thumb">CHƯA CÓ ẢNH</span>
                            @endif
                        </td>
                        <td class="wrap"><textarea form="workshop-edit-{{ $item->id }}" name="size_note" rows="1" wrap="soft" aria-label="Size" maxlength="255">{{ $item->size_note }}</textarea></td>
                        <td class="num"><input form="workshop-edit-{{ $item->id }}" name="quantity" type="number" value="{{ $item->quantity }}" aria-label="Số lượng" min="1" step="1" required></td>
                        <td class="num"><input form="workshop-edit-{{ $item->id }}" name="unit_price" type="number" value="{{ $item->unit_price ?? 0 }}" aria-label="Đơn giá" min="0" step="1"></td>
                        <td class="num strong" data-row-total>{{ number_format($item->total_amount) }}</td>
                        <td><textarea form="workshop-edit-{{ $item->id }}" name="order_name" rows="1" wrap="soft" aria-label="Tên đơn" maxlength="255">{{ $item->order_name }}</textarea></td>
                        <td><textarea form="workshop-edit-{{ $item->id }}" name="buyer_note" rows="2" maxlength="2000" aria-label="Ghi chú">{{ $item->buyer_note }}</textarea></td>
                        <td><textarea form="workshop-edit-{{ $item->id }}" name="source" rows="1" wrap="soft" aria-label="Tên nhà cung cấp" maxlength="255">{{ $item->source }}</textarea></td>
                        <td><input form="workshop-edit-{{ $item->id }}" name="ordered_date" type="date" value="{{ $item->ordered_date?->format('Y-m-d') }}" aria-label="Ngày đặt hàng" ></td>
                        <td><input form="workshop-edit-{{ $item->id }}" name="returned_date" type="date" value="{{ $item->returned_date?->format('Y-m-d') }}" aria-label="Ngày trả hàng" ></td>
                        <td><input form="workshop-edit-{{ $item->id }}" name="buyer" type="text" value="{{ $item->buyer }}" aria-label="Người mua hàng" maxlength="255"></td>
                        <td>



                                <select name="status" form="workshop-edit-{{ $item->id }}" class="status-select st-{{ $item->status }}"
                                     aria-label="Đổi trạng thái">
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected($item->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>

                        </td>
                        <td>
                            <div class="row-actions">
                                <form id="workshop-edit-{{ $item->id }}" data-inline-edit method="POST" action="{{ route('workshop-orders.update', $item) }}">@csrf @method('PUT')<input type="hidden" name="supply_source" value="{{ $item->supply_source }}"></form><span data-save-state role="status"></span>
                            </div>
                        </td>
                        <td data-delete-cell>
                                <form method="POST" action="{{ route('workshop-orders.destroy', $item) }}"
                                    onsubmit="return confirm('Xoá dòng đặt hàng của mã {{ $item->product_code }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="danger" type="submit">Xóa dòng</button>
                                </form>
                        </td>
                        <td data-stock-cell></td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="16">Nhập mã và thông tin vào dòng trống bên dưới để tạo đặt hàng mới.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="workshop-add-row-actions"><button type="button" data-add-inline-row>+ Thêm dòng sản phẩm</button></div>

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
<datalist id="workshop-product-codes">@foreach($productCodes as $code)<option value="{{ $code['code'] }}">{{ $code['name'] }}</option>@endforeach</datalist>
@include('workshop_orders.new-inline-row')
@include('workshop_orders.inline-edit')
@include('workshop_orders.product-picker')
</body>
</html>
