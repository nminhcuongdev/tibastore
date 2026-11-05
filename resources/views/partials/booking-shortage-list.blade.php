<script>
function showBookingShortages(container, shortages = []) {
    let panel = container.querySelector('[data-booking-shortages]');
    if (!panel && !shortages.length) return;
    if (!panel) {
        panel = document.createElement('div');
        panel.dataset.bookingShortages = '';
        panel.setAttribute('role', 'status');
        panel.style.cssText = 'grid-column:1/-1;margin-top:8px;padding:8px;color:#9b261b;background:#fff1ed;border-radius:6px';
        container.appendChild(panel);
    }
    panel.replaceChildren();
    panel.hidden = !shortages.length;
    if (!shortages.length) return;
    const title = document.createElement('strong');
    title.textContent = 'Đơn đã chốt cần kiểm tra lại';
    panel.appendChild(title);
    for (const row of shortages) {
        const line = document.createElement('div');
        const link = document.createElement('a');
        link.href = row.url;
        link.target = '_blank';
        link.rel = 'noopener';
        link.textContent = row.order_name;
        link.style.cssText = 'color:#2563eb;text-decoration:underline';
        line.append(link, ' · Lấy ' + row.pickup.split('-').reverse().join('/') + ' · ' + row.code + ' · Size ' + row.size + ' thiếu ' + row.shortage);
        panel.appendChild(line);
    }
}
</script>
