<div class="field full" style="grid-column:1/-1">
    <label for="rental_details_clipboard">Dán phiếu thông tin đặt thuê</label>
    <textarea id="rental_details_clipboard" rows="2" placeholder="Copy nội dung phiếu rồi dán vào đây bằng Ctrl+V"></textarea>
    <small id="rental_details_result" role="status">Chỉ điền các mục còn trống.</small>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const clipboard = document.getElementById('rental_details_clipboard');
    const result = document.getElementById('rental_details_result');
    const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/đ/g, 'd');
    const labels = {
        'ngay nhan hang': 'receipt', 'ngay lay': 'pickup_date', 'ngay dien': 'event_date',
        'ngay tra hang': 'return_date', 'ngay tra': 'return_date',
        'sdt dang ky': 'phone', 'so dien thoai dang ky': 'phone', 'so dien thoai': 'phone',
        'sdt nguoi nhan': 'recipient', 'so dien thoai nguoi nhan': 'recipient',
        'dia chi nhan hang': 'address', 'dia chi': 'address', 'ten don': 'order_name',
        'ten khach hang': 'order_name', 'ghi chu': 'note',
    };
    function parseDetails(text) {
        const values = {};
        let previous = null;
        for (const line of text.split(/\r?\n/)) {
            const match = line.match(/^\s*([^:：]+)[:：]\s*(.*)$/);
            if (match) {
                const label = normalize(match[1]).replace(/\(\s*\*\s*\)/g, '').replace(/^[-•\s]+/, '').trim();
                previous = Object.hasOwn(labels, label) ? labels[label] : null;
                if (previous) values[previous] = match[2].trim();
            } else if (['address', 'note'].includes(previous) && line.trim()) {
                values[previous] += ' ' + line.trim();
            }
        }
        return values;
    }
    function dateValue(text, defaultYear) {
        const match = text?.match(/\b(\d{1,2})[/.\-](\d{1,2})(?:[/.\-](\d{4}))?\b/);
        if (!match) return '';
        const [, day, month, year = defaultYear] = match;
        const iso = year + '-' + month.padStart(2, '0') + '-' + day.padStart(2, '0');
        const date = new Date(iso + 'T00:00:00Z');
        return Number.isFinite(date.getTime()) && date.toISOString().slice(0, 10) === iso ? iso : '';
    }
    const period = document.getElementById('return_period');
    let periodEdited = false;
    period.addEventListener('change', event => { if (event.isTrusted) periodEdited = true; });
    clipboard.addEventListener('input', () => {
        const values = parseDetails(clipboard.value);
        const fields = [];
        function fill(id, value, label) {
            const input = document.getElementById(id);
            if (!value || input.value.trim()) return false;
            input.value = value;
            if (!input.value) return false;
            fields.push(label);
            input.dispatchEvent(new Event('input', {bubbles:true}));
            input.dispatchEvent(new Event('change', {bubbles:true}));
            return true;
        }
        const year = document.getElementById('pickup_date').value.slice(0, 4)
            || new Intl.DateTimeFormat('en', {year:'numeric', timeZone:'Asia/Ho_Chi_Minh'}).format(new Date());
        fill('order_name', values.order_name, 'Tên đơn');
        fill('phone', [...new Set([values.phone, values.recipient].filter(Boolean))].join(' - '), 'Số điện thoại');
        fill('address', values.address, 'Địa chỉ');
        fill('pickup_date', dateValue(values.pickup_date, year), 'Ngày lấy');
        fill('event_date', dateValue(values.event_date, year), 'Ngày diễn');
        const returnFilled = fill('return_date', dateValue(values.return_date, year), 'Ngày trả');
        const shift = normalize(values.return_date || '').match(/\b(sang|chieu|toi)\b/)?.[1];
        if (returnFilled && shift && !periodEdited && period.value === 'sang') {
            period.value = shift;
            period.dispatchEvent(new Event('change', {bubbles:true}));
        }
        fill('note', [values.note, values.receipt && 'Khách nhận hàng ngày ' + values.receipt].filter(Boolean).join('\n'), 'Ghi chú');
        result.textContent = fields.length ? 'Đã điền ' + fields.join(', ') + '.' : 'Chưa có mục trống nào khớp với nội dung phiếu.';
    });
});
</script>
