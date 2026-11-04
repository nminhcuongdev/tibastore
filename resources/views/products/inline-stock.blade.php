<style>
.stock-editor a { color: #2563eb; text-decoration: underline; text-underline-offset: 3px; }
.stock-editor a:hover { color: #1d4ed8; }
.stock-editor{display:grid;grid-template-columns:minmax(220px,1fr) minmax(450px,2fr);gap:24px;padding:20px;background:white;border:1px solid #dce5f1;border-radius:12px}.stock-editor input{box-sizing:border-box;width:100%;min-width:0;padding:9px;border:1px solid #cbd9ec;border-radius:7px;font:inherit}.stock-identity{display:grid;gap:12px;align-content:start}.stock-identity label{display:grid;gap:5px}.stock-sizes{display:grid;gap:10px}.stock-size-line,.stock-size-head{display:grid;grid-template-columns:minmax(80px,1fr) 110px 100px 100px 90px;gap:10px;align-items:center}.stock-size-head{font-weight:700;font-size:13px}.stock-editor .thumb{width:120px;height:140px}.stock-size-notes{grid-column:1/-1;font-size:12px;color:#64748b}.stock-save-state{grid-column:1/-1;font-size:13px;color:#334155}@media(max-width:900px){.stock-editor{grid-template-columns:1fr}.stock-sizes{overflow:auto}.stock-size-line,.stock-size-head{min-width:540px}}
</style>
<form class="stock-editor" data-stock-editor action="{{ route('products.inline-stock', $product) }}">
    @csrf
    <div class="stock-identity">
        <div class="thumb" data-stock-image tabindex="0" aria-label="Ảnh sản phẩm. Nhấn Ctrl+V để dán ảnh.">@php
            $image = $variants->first(fn ($variant) => filled($variant->image_path));
        @endphp
            @if($image)<img src="{{ asset('storage/'.$image->image_path) }}" alt="{{ $product->name }}">@else CHƯA CÓ ẢNH @endif
        </div>
        <input data-stock-image-file type="file" accept="image/jpeg,image/png,image/gif,image/webp" aria-label="Tải ảnh sản phẩm từ máy tính">
        <small>Chọn ảnh từ máy hoặc copy ảnh rồi nhấn Ctrl+V. Tối đa 5MB.</small>
        <label>Mã sản phẩm<input name="code" value="{{ $product->code }}" required maxlength="50"></label>
        <label>Tên sản phẩm<input name="name" value="{{ $product->name }}" required maxlength="255"></label>
        <label>Danh mục<input name="category" value="{{ $product->category }}" maxlength="255"></label>
        <label>Giá thuê<input name="rental_price_text" value="{{ $product->rental_price_text ?? $product->rental_price }}" maxlength="255"></label>
    </div>
    <div class="stock-sizes">
        <div class="stock-size-head"><span>Size</span><span>Còn trong kho</span><span>Đang cho thuê</span><span>Tổng số hàng</span><span>Theo dõi</span></div>
        <div data-stock-lines class="stock-sizes">
        @foreach($variants as $variant)
            <div class="stock-size-line" data-stock-line data-id="{{ $variant->id }}" data-original="{{ $variant->stock_quantity }}" data-rented="{{ $rentedByProduct[$variant->id] ?? 0 }}">
                <input data-size value="{{ $variant->size }}" required maxlength="50" aria-label="Size">
                <input data-stock type="number" min="0" step="1" required value="{{ $variant->stock_quantity }}" aria-label="Tồn kho size {{ $variant->size }}">
                <span>{{ number_format($rentedByProduct[$variant->id] ?? 0) }}</span>
                <span data-size-total>{{ number_format($variant->stock_quantity + ($rentedByProduct[$variant->id] ?? 0)) }}</span>
                <a href="{{ route('products.track', $variant) }}" class="link-action">Theo dõi</a>
                <div class="stock-size-notes">
                    @if(!empty($pendingInspection[$variant->id]))<span>{{ $pendingInspection[$variant->id] }} chờ kiểm. </span>@endif
                </div>
            </div>
        @endforeach
        </div>
        <button type="button" class="link-action" data-add-stock-size>+ Thêm size</button>
        @include('products.incoming-schedule')
    </div>
    <span class="stock-save-state" role="status" data-stock-state>Sửa trực tiếp, thay đổi được tự động lưu. Số lượng nhập là hàng còn trong kho.</span>
</form>
<script>
(() => {
    const form = document.querySelector('[data-stock-editor]'), lines = form.querySelector('[data-stock-lines]'), state = form.querySelector('[data-stock-state]');
    let timer, saving = false, revision = 0, dirty = false;
    let pendingImage = null, previewUrl = null;
    const imageFile = form.querySelector('[data-stock-image-file]'), imageBox = form.querySelector('[data-stock-image]');
    function acceptStockImage(file) {
        if (!file) return;
        if (!['image/jpeg','image/png','image/gif','image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            state.textContent = 'Chọn ảnh JPG, PNG, GIF hoặc WebP tối đa 5MB.'; imageFile.value = ''; return;
        }
        pendingImage = file;
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        const image = document.createElement('img'); image.src = previewUrl; image.alt = 'Ảnh vừa chọn'; imageBox.replaceChildren(image);
        changed();
    }
    imageFile.addEventListener('change', () => acceptStockImage(imageFile.files[0]));
    form.addEventListener('paste', event => {
        const item = Array.from(event.clipboardData?.items || []).find(item => item.type.startsWith('image/'));
        if (item) { event.preventDefault(); acceptStockImage(item.getAsFile()); }
    });
    window.addEventListener('pagehide', () => { if (previewUrl) URL.revokeObjectURL(previewUrl); });
    function changed() { revision++; dirty = true; clearTimeout(timer); state.textContent = 'Chờ tự lưu…'; timer = setTimeout(save, 800); }
    form.addEventListener('input', changed); form.addEventListener('change', changed);
    form.addEventListener('submit', event => { event.preventDefault(); save(); });
    form.querySelector('[data-add-stock-size]').addEventListener('click', () => {
        const row = document.createElement('div'); row.className = 'stock-size-line'; row.dataset.stockLine = ''; row.dataset.original = '0'; row.dataset.rented = '0';
        row.innerHTML = '<input data-size required maxlength="50" placeholder="Size mới" aria-label="Size mới"><input data-stock type="number" required min="0" step="1" value="0" aria-label="Tồn kho size mới"><span>0</span><span data-size-total>0</span><button type="button">Bỏ</button>';
        row.querySelector('button').addEventListener('click', () => { if (!row.dataset.id && !saving) { row.remove(); changed(); } });
        lines.appendChild(row); row.querySelector('input').focus(); changed();
    });
    async function save() {
        if (saving || !dirty || !form.checkValidity()) return;
        saving = true; const sent = revision, sentImage = pendingImage;
        const body = new FormData(form); body.append('_method', 'PATCH');
        if (sentImage) body.append('image', sentImage);
        const rows = Array.from(lines.querySelectorAll('[data-stock-line]'));
        rows.forEach((row, index) => {
            if (row.dataset.id) body.append(`variants[${index}][id]`, row.dataset.id);
            body.append(`variants[${index}][size]`, row.querySelector('[data-size]').value);
            body.append(`variants[${index}][stock_quantity]`, row.querySelector('[data-stock]').value);
            body.append(`variants[${index}][original_quantity]`, row.dataset.original);
        });
        state.textContent = 'Đang lưu…';
        try {
            const response = await fetch(form.action, {method:'POST', body, credentials:'same-origin', headers:{Accept:'application/json'}});
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : data.message);
            if (pendingImage === sentImage) { pendingImage = null; imageFile.value = ''; }
            data.variants.forEach((variant, index) => {
                rows[index].dataset.id = variant.id; rows[index].dataset.original = variant.stock_quantity;
                rows[index].querySelector('[data-size-total]').textContent = Number(rows[index].querySelector('[data-stock]').value) + Number(rows[index].dataset.rented);
                rows[index].querySelector('button')?.remove();
            });
            const total = data.variants.reduce((sum, variant) => sum + Number(variant.stock_quantity), 0);
            document.querySelector('[data-stock-metric-quantity]').textContent = total.toLocaleString('vi-VN');
            const totalMetric = document.querySelector('[data-stock-metric-total]');
            totalMetric.textContent = (total + Number(totalMetric.dataset.rented)).toLocaleString('vi-VN');
            if (sent === revision) { dirty = false; state.textContent = 'Đã lưu kho'; }
        } catch(error) { state.textContent = 'Chưa lưu: ' + error.message; }
        finally { saving = false; if (sent !== revision) { clearTimeout(timer); timer = setTimeout(save, 800); } }
    }
    window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
    let refreshingSchedule = false;
    async function refreshSchedule() {
        if (refreshingSchedule || document.hidden) return;
        refreshingSchedule = true;
        try {
            const response = await fetch(@json(route('products.incoming-schedule', $product)), {credentials:'same-origin', cache:'no-store'});
            if (!response.ok || response.redirected) return;
            const html = await response.text();
            const template = document.createElement('template'); template.innerHTML = html;
            const section = template.content.querySelector('[data-incoming-schedule]');
            if (section) form.querySelector('[data-incoming-schedule]').replaceWith(section);
        } catch (_) { /* Keep the existing schedule until the next successful refresh. */ }
        finally { refreshingSchedule = false; }
    }
    window.addEventListener('focus', refreshSchedule);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshSchedule(); });
    setInterval(refreshSchedule, 20000);
    document.addEventListener('personal-history-request', event => {
        if (dirty || saving) {
            event.preventDefault();
            const historyState = document.querySelector('[data-personal-state]');
            if (historyState) historyState.textContent = 'Vui lòng chờ thay đổi kho tự lưu xong trước khi hoàn tác hoặc làm lại.';
        }
    });
})();
</script>
