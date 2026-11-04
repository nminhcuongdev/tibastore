<section style="margin-top:16px;padding:14px;border:1px solid #d8e4f5;border-radius:8px;background:#f8fbff" data-create-arrivals>
    <strong>Dự kiến hàng về</strong>
    <div data-arrival-lines style="display:grid;gap:10px;margin:12px 0"></div>
    <button type="button" class="button secondary" data-add-arrival>+ Thêm lịch hàng về</button>
    <small style="display:block;margin-top:8px">Chọn size đã nhập bên trên, ngày về và số lượng dự kiến. Chưa cộng vào tồn kho.</small>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const panel = document.querySelector('[data-create-arrivals]'), sizes = document.getElementById('size_rows');
    const list = panel.querySelector('[data-arrival-lines]');
    function sync() {
        const sizeRows = Array.from(sizes.querySelectorAll('.size-row'));
        const counters = new Map();
        Array.from(list.children).forEach(line => {
            const select = line.querySelector('select'), date = line.querySelector('[data-arrival-date]'), quantity = line.querySelector('[data-arrival-quantity]');
            const selectedRow = line.selectedSizeRow;
            select.replaceChildren(new Option('Chọn size', ''));
            sizeRows.forEach((row, index) => {
                const name = row.querySelector('[data-size]').value.trim();
                select.add(new Option(name || 'Size chưa nhập #' + (index + 1), String(index)));
            });
            const index = sizeRows.indexOf(selectedRow);
            select.value = index >= 0 ? String(index) : '';
            const filled = date.value || Number(quantity.value) > 0 || select.value !== '';
            select.required = date.required = quantity.required = !!filled;
            quantity.min = filled ? '1' : '0';
            line.querySelectorAll('input[type=hidden]').forEach(input => input.remove());
            if (index < 0 || !filled) return;
            const receiptIndex = counters.get(index) || 0; counters.set(index, receiptIndex + 1);
            [['expected_receive_date', date.value], ['expected_receive_quantity', quantity.value]].forEach(([field, value]) => {
                const input = document.createElement('input'); input.type = 'hidden'; input.name = `variants[${index}][expected_receipts][${receiptIndex}][${field}]`; input.value = value; line.appendChild(input);
            });
        });
    }
    function add(data = {}, sizeIndex = null) {
        const line = document.createElement('div'); line.style.cssText = 'display:grid;grid-template-columns:minmax(100px,1fr) minmax(150px,1fr) 100px 40px;gap:8px';
        line.innerHTML = '<select aria-label="Size dự kiến về"></select><input data-arrival-date type="date" aria-label="Ngày hàng về"><input data-arrival-quantity type="number" min="0" step="1" placeholder="Số lượng" aria-label="Số lượng dự kiến"><button type="button" aria-label="Bỏ lịch hàng về">×</button>';
        line.selectedSizeRow = sizeIndex === null ? null : sizes.querySelectorAll('.size-row')[sizeIndex];
        line.querySelector('[data-arrival-date]').value = data.expected_receive_date || '';
        line.querySelector('[data-arrival-quantity]').value = data.expected_receive_quantity || '';
        line.querySelector('select').addEventListener('change', event => { line.selectedSizeRow = event.target.value === '' ? null : sizes.querySelectorAll('.size-row')[Number(event.target.value)]; sync(); });
        line.querySelector('button').addEventListener('click', () => { line.remove(); sync(); });
        list.appendChild(line); sync();
    }
    list.addEventListener('input', event => { if (event.target.tagName !== 'SELECT') sync(); }); sizes.addEventListener('input', sync);
    new MutationObserver(sync).observe(sizes, {childList:true});
    panel.querySelector('[data-add-arrival]').addEventListener('click', () => add());
    const initial = @json($variantRows);
    initial.forEach((variant, index) => (variant.expected_receipts || []).filter(receipt => receipt.expected_receive_date || Number(receipt.expected_receive_quantity) > 0).forEach(receipt => add(receipt, index)));
});
</script>
