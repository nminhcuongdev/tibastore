<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>Tìm mã bằng ảnh | Tiba Boutique</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font: 15px/1.5 "Segoe UI", Arial, sans-serif; color: #1e293b; background: #f5f8ff; }
        main { padding: 28px; max-width: 1400px; margin: auto; }
        h1 { color: #172f60; margin: 0 0 8px; }
        a { color: #1e40af; }
        .intro { color: #64748b; margin: 0 0 22px; }
        .search-box { background: white; border: 1px solid #d8e4f5; border-radius: 16px; padding: 24px; }
        .upload { display: flex; gap: 22px; align-items: center; flex-wrap: wrap; }
        .preview { width: 170px; height: 200px; object-fit: contain; background: #eff6ff; border-radius: 12px; }
        .controls { flex: 1; min-width: min(280px, 100%); }
        label { display: block; font-weight: 700; margin-bottom: 8px; }
        input { width: 100%; padding: 12px; border: 1px solid #c3d4ed; border-radius: 8px; }
        .paste-area { display: block; width: 100%; margin-top: 12px; padding: 16px; border: 2px dashed #b4c8ee; border-radius: 10px; background: #f5f8ff; font: inherit; resize: vertical; }
        .paste-area:focus { outline: 2px solid #1d4ed8; }
        button, .back { display: inline-block; padding: 11px 17px; border: 1px solid #c3d4ed; border-radius: 9px; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; background: white; color: #1e40af; }
        button.primary { background: #1d4ed8; color: white; border-color: #1d4ed8; }
        button:disabled { opacity: .55; cursor: wait; }
        .actions { display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap; }
        .hint { color: #64748b; font-size: 13px; margin: 12px 0; }
        .progress { margin-top: 18px; }
        progress { display: block; width: 100%; height: 12px; }
        [data-status] { margin-top: 10px; }
        .error { color: #b4233f; }
        .results { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-top: 20px; }
        .result { border: 1px solid #d8e4f5; background: white; border-radius: 12px; overflow: hidden; text-decoration: none; }
        .result img { width: 100%; height: 230px; object-fit: contain; background: #fff; }
        .result div { padding: 14px; overflow-wrap: anywhere; }
        .result strong { display: block; font-size: 18px; color: #1e40af; }
        .result span { display: block; color: #475569; }
        .result small { color: #64748b; }
        [hidden] { display: none !important; }
        @media(max-width:600px) { main { padding: 18px 12px; } .search-box { padding: 16px; } .preview { width: 100%; height: 180px; } }
    </style>
</head>
<body>
<div class="layout">
    @include('partials.sidebar', ['active' => 'products'])
    <div class="page"><main data-image-search data-catalog-url="{{ route('products.image-search.catalog') }}">
        <h1>Tìm mã bằng ảnh</h1>
        <p class="intro">Chọn ảnh trang phục hoặc dán ảnh bằng Ctrl+V để tìm các mẫu gần giống trong kho.</p>
        <section class="search-box" aria-label="Chọn ảnh tìm kiếm">
            <div class="upload">
                <img class="preview" data-preview alt="Ảnh bạn chọn" hidden>
                <div class="controls">
                    <label for="search-image">Ảnh chụp thực tế hoặc ảnh mẫu</label>
                    <input id="search-image" type="file" accept="image/jpeg,image/png,image/webp">
                    <textarea class="paste-area" data-paste-area aria-label="Dán ảnh tìm kiếm" placeholder="Bấm vào đây rồi nhấn Ctrl+V để dán ảnh, hoặc kéo ảnh vào đây." rows="2"></textarea>
                    <p class="hint" data-selection>Chưa chọn ảnh.</p>
                    <p class="hint">JPG, PNG hoặc WebP, tối đa 10 MB. Ảnh rõ trang phục, ít vật che và nền đơn giản sẽ dễ tìm hơn.</p>
                    <div class="actions">
                        <button type="button" data-paste>Dán ảnh đã sao chép</button>
                        <button class="primary" type="button" data-search disabled>Tìm mã bằng ảnh</button>
                        <button type="button" data-cancel hidden>Dừng tìm</button>
                        <a class="back" href="{{ route('products.index') }}">Về kho hàng</a>
                    </div>
                </div>
            </div>
            <p class="hint">Lần đầu cần Internet để tải mô hình AI và có thể mất vài phút phân tích ảnh kho. Những lần sau dùng dữ liệu đã lưu trên trình duyệt. Ảnh bạn chọn được xử lý trên thiết bị, không gửi đến dịch vụ AI bên ngoài.</p>
            <div class="progress" data-progress-box hidden><progress data-progress max="100" value="0" aria-label="Tiến độ tìm ảnh"></progress></div>
            <p data-status role="status" aria-live="polite">Chọn một ảnh để bắt đầu.</p>
        </section>
        <h2 data-result-title hidden>Kết quả gần giống</h2>
        <p class="hint" data-result-note hidden>Đây là gợi ý theo hình ảnh, không phải xác nhận đúng mã. Hãy đối chiếu mẫu, màu và chi tiết trước khi chọn.</p>
        <div class="results" data-results></div>
        <noscript>Cần bật JavaScript để sử dụng tìm kiếm bằng ảnh.</noscript>
    </main></div>
</div>
<script type="module" src="{{ asset('js/product-image-search.js') }}?v={{ filemtime(public_path('js/product-image-search.js')) }}"></script>
</body>
</html>
