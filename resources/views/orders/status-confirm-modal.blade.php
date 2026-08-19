@php use App\Models\Order; @endphp
<style>
    .sconfirm {
        align-items: center;
        background: rgba(63, 39, 48, .72);
        display: none;
        inset: 0;
        justify-content: center;
        padding: 24px;
        position: fixed;
        z-index: 1150;
    }
    .sconfirm.is-open { display: flex; }
    .sconfirm__box {
        background: #fff;
        border: 1px solid #f0d3dc;
        border-radius: 12px;
        box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
        max-height: 88vh;
        max-width: 680px;
        overflow-y: auto;
        padding: 24px;
        width: 100%;
    }
    .sconfirm__title {
        color: #6f253f;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 24px;
        margin: 0 0 12px;
    }
    .sconfirm__flow {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
    }
    .sconfirm__chip {
        border-radius: 999px;
        font-size: 13px;
        font-weight: 900;
        padding: 7px 13px;
    }
    .sconfirm__arrow { color: #8b6672; font-weight: 900; }
    .sconfirm__effect {
        border-radius: 8px;
        font-size: 14px;
        font-weight: 800;
        margin-bottom: 14px;
        padding: 11px 13px;
    }
    .sconfirm__effect.is-down { background: #fff7d6; color: #8a5a00; }
    .sconfirm__effect.is-up { background: #e8f7ef; color: #247857; }
    .sconfirm__effect.is-none { background: #f3eef1; color: #6b5560; }
    .sconfirm__table-shell { border: 1px solid #f2d3dc; border-radius: 8px; overflow: hidden; }
    .sconfirm__table { border-collapse: collapse; width: 100%; }
    .sconfirm__table th,
    .sconfirm__table td { border-bottom: 1px solid #f7e3e9; padding: 10px 12px; text-align: left; }
    .sconfirm__table thead th {
        background: #fff0f4;
        color: #81304c;
        font-size: 12px;
        text-transform: uppercase;
    }
    .sconfirm__table th.num, .sconfirm__table td.num { text-align: right; }
    .sconfirm__table tbody tr:last-child td { border-bottom: 0; }
    .sconfirm__code { color: #a13b60; font-weight: 800; }
    .sconfirm__delta { font-weight: 900; }
    .sconfirm__delta.is-down { color: #b4233f; }
    .sconfirm__delta.is-up { color: #247857; }
    .sconfirm__row-bad td { background: #fdecec; }
    .sconfirm__alert {
        background: #fdecec;
        border-left: 5px solid #b4233f;
        border-radius: 8px;
        color: #b4233f;
        font-weight: 800;
        line-height: 1.5;
        margin-top: 14px;
        padding: 12px 14px;
    }
    .sconfirm__hint { color: #8b6672; font-size: 13px; line-height: 1.5; margin: 12px 0 0; }
    .sconfirm__actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
</style>

<div class="sconfirm" data-sconfirm aria-hidden="true">
    <div class="sconfirm__box" role="dialog" aria-modal="true" aria-labelledby="sconfirm-title">
        <h2 id="sconfirm-title" class="sconfirm__title">Xác nhận đổi trạng thái</h2>
        <p class="sconfirm__hint" style="margin: 0 0 12px;">Đơn: <strong data-sconfirm-order></strong></p>
        <div class="sconfirm__flow">
            <span class="sconfirm__chip" data-sconfirm-from></span>
            <span class="sconfirm__arrow">&rarr;</span>
            <span class="sconfirm__chip" data-sconfirm-to></span>
        </div>
        <div class="sconfirm__effect" data-sconfirm-effect></div>
        <div class="sconfirm__table-shell" data-sconfirm-table-shell>
            <table class="sconfirm__table">
                <thead>
                    <tr>
                        <th>Mã hàng</th>
                        <th>Size</th>
                        <th class="num">Số lượng</th>
                        <th class="num">Tồn hiện tại</th>
                        <th class="num">Sau khi đổi</th>
                    </tr>
                </thead>
                <tbody data-sconfirm-body></tbody>
            </table>
        </div>
        <div class="sconfirm__alert" data-sconfirm-alert style="display: none;"></div>
        <p class="sconfirm__hint" data-sconfirm-note></p>
        <div class="sconfirm__actions">
            <button type="button" class="button secondary" data-sconfirm-cancel>Hủy</button>
            <button type="button" class="button" data-sconfirm-ok>Xác nhận đổi</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const STOCK_OUT_STATUSES = @json(Order::STOCK_OUT_STATUSES);
        const STATUS_LABELS = @json(Order::statuses());
        const modal = document.querySelector('[data-sconfirm]');
        if (!modal) return;

        const orderEl = modal.querySelector('[data-sconfirm-order]');
        const fromEl = modal.querySelector('[data-sconfirm-from]');
        const toEl = modal.querySelector('[data-sconfirm-to]');
        const effectEl = modal.querySelector('[data-sconfirm-effect]');
        const tableShell = modal.querySelector('[data-sconfirm-table-shell]');
        const bodyEl = modal.querySelector('[data-sconfirm-body]');
        const alertEl = modal.querySelector('[data-sconfirm-alert]');
        const noteEl = modal.querySelector('[data-sconfirm-note]');
        let activeSelect = null;

        function addCell(row, text, className) {
            const cell = document.createElement('td');
            if (className) cell.className = className;
            cell.textContent = text;
            row.appendChild(cell);
            return cell;
        }

        // Kho chi doi khi don buoc qua ranh gioi "hang phai nam ngoai kho".
        // Cung ben ranh gioi (vd Dang soan -> Da soan xong) thi kho giu nguyen.
        function stockDirection(select, nextStatus) {
            const currentlyOut = select.dataset.currentlyOut === '1';
            const nextOut = STOCK_OUT_STATUSES.indexOf(nextStatus) !== -1;

            if (nextOut && !currentlyOut) return -1;
            if (!nextOut && currentlyOut) return 1;

            return 0;
        }

        function openConfirm(select) {
            activeSelect = select;

            const nextStatus = select.value;
            const currentStatus = select.dataset.current;
            const direction = stockDirection(select, nextStatus);

            orderEl.textContent = select.dataset.orderName || '';
            fromEl.textContent = STATUS_LABELS[currentStatus] || currentStatus;
            fromEl.className = 'sconfirm__chip status-' + currentStatus;
            toEl.textContent = STATUS_LABELS[nextStatus] || nextStatus;
            toEl.className = 'sconfirm__chip status-' + nextStatus;

            let items = [];
            try { items = JSON.parse(select.dataset.items || '[]'); } catch (e) { items = []; }

            // Dong "chua chot size" khong giu kho nen khong tinh vao thay doi ton.
            const affected = items.filter(function (it) { return !it.size_pending; });

            bodyEl.innerHTML = '';
            alertEl.style.display = 'none';
            alertEl.textContent = '';

            if (direction === 0) {
                effectEl.className = 'sconfirm__effect is-none';
                effectEl.textContent = 'Tồn kho không thay đổi — chỉ đổi trạng thái đơn.';
                tableShell.style.display = 'none';
                noteEl.textContent = '';
                openModal();
                return;
            }

            tableShell.style.display = '';

            const total = affected.reduce(function (sum, it) { return sum + Number(it.quantity || 0); }, 0);

            if (direction < 0) {
                effectEl.className = 'sconfirm__effect is-down';
                effectEl.textContent = 'Hàng rời kho: tồn sẽ GIẢM tổng cộng ' + total.toLocaleString('vi-VN') + ' cái.';
            } else {
                effectEl.className = 'sconfirm__effect is-up';
                effectEl.textContent = 'Hàng về kho: tồn sẽ TĂNG tổng cộng ' + total.toLocaleString('vi-VN') + ' cái.';
            }

            const blocked = [];

            affected.forEach(function (it) {
                const quantity = Number(it.quantity || 0);
                const stock = Number(it.stock || 0);
                const after = stock + direction * quantity;
                const row = document.createElement('tr');

                addCell(row, it.code, 'sconfirm__code');
                addCell(row, it.size);
                addCell(row, (direction < 0 ? '-' : '+') + quantity.toLocaleString('vi-VN'),
                    'num sconfirm__delta ' + (direction < 0 ? 'is-down' : 'is-up'));
                addCell(row, stock.toLocaleString('vi-VN'), 'num');
                addCell(row, after.toLocaleString('vi-VN'), 'num');

                // Server chan neu ton hien tai khong du de tru — bao truoc thay vi de bam roi im lang.
                if (direction < 0 && stock < quantity) {
                    row.className = 'sconfirm__row-bad';
                    blocked.push(it.code + ' - size ' + it.size + ': cần ' + quantity + ' nhưng tồn chỉ ' + stock);
                }

                bodyEl.appendChild(row);
            });

            if (affected.length === 0) {
                const row = document.createElement('tr');
                addCell(row, 'Đơn không có dòng hàng nào giữ kho.').colSpan = 5;
                bodyEl.appendChild(row);
            }

            if (blocked.length > 0) {
                alertEl.style.display = '';
                alertEl.textContent = 'Không đủ tồn để trừ, hệ thống sẽ từ chối đổi trạng thái: ' + blocked.join('; ') + '.';
            }

            noteEl.textContent = direction < 0
                ? 'Số lượng trên là toàn bộ hàng của đơn. Nếu sau này đơn được kiểm thiếu, phần hoàn lại kho sẽ theo số thực nhận.'
                : 'Toàn bộ hàng của đơn được hoàn lại kho.';

            openModal();
        }

        function openModal() {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal(revert) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');

            if (revert && activeSelect) {
                activeSelect.value = activeSelect.dataset.current;
            }

            activeSelect = null;
        }

        // check-modal goi ham nay cho moi trang thai khac "Da kiem".
        window.openStatusConfirm = openConfirm;

        modal.querySelector('[data-sconfirm-ok]').addEventListener('click', function () {
            if (!activeSelect) return;

            const form = activeSelect.form;
            activeSelect = null;
            closeModal(false);
            form.submit();
        });

        modal.querySelector('[data-sconfirm-cancel]').addEventListener('click', function () { closeModal(true); });
        modal.addEventListener('click', function (event) { if (event.target === modal) closeModal(true); });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal(true);
        });
    })();
</script>
