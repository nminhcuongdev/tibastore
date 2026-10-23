<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tồn kho theo ngày</title>
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
            margin-bottom: 14px;
        }
        .field { display: grid; gap: 6px; }
        label { color: #334e75; font-size: 13px; font-weight: 700; }
        input {
            background: #fff;
            border: 1px solid #c3d4ed;
            border-radius: 8px;
            color: #1e293b;
            font-size: 15px;
            min-height: 44px;
            padding: 10px 13px;
        }
        input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .16); outline: none; }
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
        .legend { color: #475569; display: flex; flex-wrap: wrap; font-size: 13px; gap: 16px; margin-bottom: 14px; }
        .legend span { align-items: center; display: inline-flex; gap: 6px; }
        .dot { border-radius: 3px; display: inline-block; height: 14px; width: 14px; }
        .dot.ok { background: #e8f7ef; border: 1px solid #9bd9ba; }
        .dot.low { background: #fff7d6; border: 1px solid #e5c96a; }
        .dot.out { background: #ffe1e1; border: 1px solid #eaa; }
        .table-shell {
            background: #fff;
            border: 1px solid #d8e4f5;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(30, 64, 175, .08);
            overflow-x: auto;
        }
        table { border-collapse: separate; border-spacing: 0; width: 100%; }
        th, td { border-bottom: 1px solid #e7eef8; border-right: 1px solid #e7eef8; padding: 8px 10px; text-align: center; white-space: nowrap; }
        thead th {
            background: #eff6ff;
            color: #1e3a5f;
            font-size: 12px;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        thead th.today { background: #dbeafe; }
        th.prod, td.prod {
            background: #fff;
            left: 0;
            min-width: 220px;
            position: sticky;
            text-align: left;
            z-index: 1;
        }
        thead th.prod { z-index: 3; background: #eff6ff; }
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
        .code { color: #1e40af; font-weight: 800; }
        .muted { color: #64748b; font-size: 12px; }
        .dow { color: #a06; font-size: 11px; font-weight: 700; }
        .cell { font-weight: 800; }
        .cell-ok { background: #e8f7ef; color: #1f7d55; }
        .cell-low { background: #fff7d6; color: #8a5a00; }
        .cell-out { background: #ffe1e1; color: #b4233f; }
        td.today, thead th.today { box-shadow: inset 2px 0 0 #1d4ed8, inset -2px 0 0 #1d4ed8; }
        .prod-cell { align-items: center; display: flex; gap: 10px; }
        .prod-info { min-width: 0; }
        .thumb {
            align-items: center;
            background: #e5efff;
            border: 1px solid #cbdcf5;
            border-radius: 8px;
            color: #1e40af;
            cursor: default;
            display: flex;
            flex: 0 0 auto;
            font-family: inherit;
            font-size: 10px;
            font-weight: 800;
            height: 52px;
            justify-content: center;
            overflow: hidden;
            padding: 0;
            text-align: center;
            width: 52px;
        }
        .thumb.has-image { cursor: zoom-in; }
        .thumb.has-image:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, .16); }
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
        .empty { color: #64748b; padding: 36px; text-align: center; }
    </style>
    @include('partials.compact')
</head>
<body>
<div class="layout">
@include('partials.sidebar', ['active' => 'daily-stock'])
<main class="content">
    <h1>Tồn kho theo ngày</h1>
    <p class="sub">Số lượng <strong>khả dụng</strong> của từng sản phẩm theo mỗi ngày = tồn hiện có + hàng nhập dự kiến về − số đang cho thuê (đơn phủ ngày đó).</p>

    <form class="toolbar" method="GET" action="{{ route('products.daily-stock') }}">
        <div class="field">
            <label for="q">Tìm mã / tên sản phẩm</label>
            <input id="q" name="q" type="search" value="{{ $query }}" placeholder="VD: N20, váy...">
        </div>
        <div class="field">
            <label for="from">Từ ngày</label>
            <input id="from" name="from" type="date" value="{{ $dateFrom->format('Y-m-d') }}">
        </div>
        <div class="field">
            <label for="to">Đến ngày</label>
            <input id="to" name="to" type="date" value="{{ $dateTo->format('Y-m-d') }}">
        </div>
        <button class="button" type="submit">Xem</button>
        <a class="button secondary" href="{{ route('products.daily-stock') }}">Về hôm nay</a>
    </form>

    <div class="legend">
        <span><i class="dot ok"></i> Còn nhiều</span>
        <span><i class="dot low"></i> Còn ít (1–2)</span>
        <span><i class="dot out"></i> Hết / vượt (≤ 0)</span>
        <span class="muted">Tối đa 60 ngày mỗi lần xem. Cột viền xanh dương là hôm nay.</span>
    </div>

    @php $todayStr = now()->toDateString(); $dow = ['CN','T2','T3','T4','T5','T6','T7']; @endphp

    <div class="table-shell">
        <table>
            <thead>
                <tr>
                    <th class="prod">Sản phẩm</th>
                    @foreach ($dates as $date)
                        <th class="{{ $date->toDateString() === $todayStr ? 'today' : '' }}">
                            {{ $date->format('d/m') }}
                            <div class="dow">{{ $dow[$date->dayOfWeek] }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="prod">
                            <div class="prod-cell">
                                @if ($product->image_path)
                                    <button class="thumb has-image" type="button" data-full-image="{{ asset('storage/' . $product->image_path) }}" aria-label="Phóng to ảnh {{ $product->name }}">
                                        <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}">
                                    </button>
                                @else
                                    <div class="thumb">CHƯA CÓ ẢNH</div>
                                @endif
                                <div class="prod-info">
                                    <div><span class="code">{{ $product->code }}</span> — size {{ $product->size }}</div>
                                    <div class="muted">{{ $product->name }} · tồn hiện tại: {{ number_format($product->stock_quantity) }}</div>
                                    @if (! empty($pendingInspection[$product->id]))
                                        <div class="pending-check" title="Đã về kho, chưa kiểm">
                                            {{ number_format($pendingInspection[$product->id]) }} chờ kiểm
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        @foreach ($dates as $date)
                            @php
                                $v = $availability[$product->id][$date->toDateString()] ?? 0;
                                $cls = $v <= 0 ? 'cell-out' : ($v <= 2 ? 'cell-low' : 'cell-ok');
                                $isToday = $date->toDateString() === $todayStr;
                            @endphp
                            <td class="cell {{ $cls }} {{ $isToday ? 'today' : '' }}">{{ $v }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="{{ count($dates) + 1 }}">Không có sản phẩm phù hợp.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</main>
</div>
<div class="image-lightbox" data-image-lightbox aria-hidden="true">
    <img data-lightbox-image src="" alt="">
</div>
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
</script>
</body>
</html>
