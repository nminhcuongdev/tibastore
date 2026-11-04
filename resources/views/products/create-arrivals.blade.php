<section style="margin-top:16px;padding:14px;border:1px solid #d8e4f5;border-radius:8px;background:#f8fbff" data-create-arrivals>
    <strong>Dự kiến hàng về</strong>
    <div data-arrival-lines style="display:grid;gap:10px;margin:12px 0"></div>
    <button type="button" class="button secondary" data-add-arrival>+ Thêm lịch hàng về</button>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const panel = document.querySelector('[data-create-arrivals]');
    const list = panel.querySelector('[data-arrival-lines]');
    function add(value = '') {
        const line = document.createElement('div'); line.style.cssText = 'display:grid;grid-template-columns:minmax(150px,1fr) 40px;gap:8px';
        const date = document.createElement('input'); date.type = 'date'; date.name = 'arrival_dates[]'; date.value = value; date.setAttribute('aria-label', 'Ngày dự kiến hàng về');
        const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = '×'; remove.setAttribute('aria-label', 'Bỏ lịch hàng về');
        remove.addEventListener('click', () => line.remove()); line.append(date, remove); list.appendChild(line);
    }
    panel.querySelector('[data-add-arrival]').addEventListener('click', () => add());
    const initial = @json(old('arrival_dates', $product->arrival_dates ?? []));
    initial.forEach(date => add(date || ''));
});
</script>
