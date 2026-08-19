@php use App\Models\Order; @endphp
<style>
    .check-modal {
        align-items: center;
        background: rgba(63, 39, 48, .72);
        display: none;
        inset: 0;
        justify-content: center;
        padding: 24px;
        position: fixed;
        z-index: 1100;
    }
    .check-modal.is-open { display: flex; }
    .check-modal__box {
        background: #fff;
        border: 1px solid #f0d3dc;
        border-radius: 12px;
        box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
        max-height: 88vh;
        max-width: 560px;
        overflow-y: auto;
        padding: 24px;
        width: 100%;
    }
    .check-modal__title {
        color: #6f253f;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 24px;
        margin: 0 0 8px;
    }
    .check-modal__hint {
        color: #8b6672;
        font-size: 13px;
        line-height: 1.5;
        margin: 0 0 16px;
    }
    .check-modal__items { display: grid; gap: 10px; margin-bottom: 16px; }
    .check-item {
        align-items: center;
        background: #fffafb;
        border: 1px solid #f2d3dc;
        border-radius: 8px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: space-between;
        padding: 12px 14px;
    }
    .check-item__label { color: #3f2730; font-weight: 800; }
    .check-item__qty { align-items: center; display: flex; gap: 10px; }
    .check-item__qty span { color: #8b6672; font-size: 13px; font-weight: 700; }
    .check-item__qty input {
        border: 1px solid #ebc5d2;
        border-radius: 8px;
        font-size: 15px;
        min-height: 40px;
        padding: 8px 10px;
        width: 90px;
    }
    .check-modal__label { color: #7a344c; display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; }
    .check-modal__note {
        border: 1px solid #ebc5d2;
        border-radius: 8px;
        font-family: inherit;
        font-size: 15px;
        padding: 10px 13px;
        resize: vertical;
        width: 100%;
    }
    .check-modal__note:focus,
    .check-item__qty input:focus {
        border-color: #c9577d;
        box-shadow: 0 0 0 3px rgba(201, 87, 125, .16);
        outline: none;
    }
    .check-modal__actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
</style>

<div class="check-modal" data-check-modal aria-hidden="true">
    <div class="check-modal__box" role="dialog" aria-modal="true" aria-labelledby="check-modal-title">
        <form method="POST" data-check-form>
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="{{ Order::CHECKED_STATUS }}">
            <h2 id="check-modal-title" class="check-modal__title">Kiểm đơn: <span data-check-order-name></span></h2>
            <p class="check-modal__hint">Mặc định số hoàn lại bằng số gửi đi. Chỉ cần chỉnh những mã hàng bị thiếu; phần thiếu được coi là mất/hỏng và không hoàn lại kho.</p>
            <div class="check-modal__items" data-check-items></div>
            <label class="check-modal__label" for="compensation-amount">Tiền bồi thường</label>
            <input id="compensation-amount" type="number" name="compensation_amount" min="0" step="1" value="0" class="check-modal__note" data-check-compensation placeholder="0">
            <p class="check-modal__hint" style="margin: 6px 0 16px;">Tiền khách bồi thường cho phần thiếu/hỏng. Số này sẽ được cộng vào tổng đơn.</p>
            <label class="check-modal__label" for="check-note">Ghi chú kiểm đơn</label>
            <textarea id="check-note" name="check_note" rows="3" class="check-modal__note" placeholder="VD: thiếu 1 áo size M do rách..."></textarea>
            <div class="check-modal__actions">
                <button type="button" class="button secondary" data-check-cancel>Hủy</button>
                <button type="submit" class="button">Xác nhận đã kiểm</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const CHECKED_STATUS = @json(Order::CHECKED_STATUS);
        const modal = document.querySelector('[data-check-modal]');
        if (!modal) return;

        const form = modal.querySelector('[data-check-form]');
        const itemsBox = modal.querySelector('[data-check-items]');
        const orderNameEl = modal.querySelector('[data-check-order-name]');
        const noteEl = modal.querySelector('[name="check_note"]');
        const compensationEl = modal.querySelector('[data-check-compensation]');
        let activeSelect = null;

        function openModal(select) {
            activeSelect = select;
            form.setAttribute('action', select.dataset.checkUrl || select.form.getAttribute('action'));
            orderNameEl.textContent = select.dataset.orderName || '';
            noteEl.value = select.dataset.checkNote || '';
            compensationEl.value = select.dataset.compensation || '0';

            let items = [];
            try { items = JSON.parse(select.dataset.items || '[]'); } catch (e) { items = []; }

            itemsBox.innerHTML = '';
            items.forEach(function (it) {
                const row = document.createElement('div');
                row.className = 'check-item';

                const label = document.createElement('div');
                label.className = 'check-item__label';
                label.textContent = it.code + ' - Size ' + it.size + (it.name ? ' · ' + it.name : '');

                const qty = document.createElement('div');
                qty.className = 'check-item__qty';

                const info = document.createElement('span');
                info.textContent = 'Gửi đi: ' + it.quantity;

                const input = document.createElement('input');
                input.type = 'number';
                input.name = 'returned_quantities[' + it.id + ']';
                input.min = '0';
                input.max = String(it.quantity);
                input.step = '1';
                input.value = String(it.returned);

                // Chặn nhập vượt số gửi đi / số âm ngay khi gõ.
                input.addEventListener('input', function () {
                    if (this.value === '') return;
                    let v = Math.floor(Number(this.value));
                    if (isNaN(v)) { this.value = ''; return; }
                    if (v > it.quantity) v = it.quantity;
                    if (v < 0) v = 0;
                    this.value = String(v);
                });

                // Bỏ trống thì trả về mặc định = số gửi đi.
                input.addEventListener('blur', function () {
                    if (this.value === '' || isNaN(Number(this.value))) {
                        this.value = String(it.quantity);
                    }
                });

                qty.appendChild(info);
                qty.appendChild(input);
                row.appendChild(label);
                row.appendChild(qty);
                itemsBox.appendChild(row);
            });

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

        document.querySelectorAll('select.status-select[name="status"]').forEach(function (select) {
            select.addEventListener('change', function (event) {
                if (this.value === CHECKED_STATUS) {
                    event.preventDefault();
                    openModal(this);
                    return;
                }

                // Cac trang thai con lai di qua modal xac nhan (neu trang co nhung).
                if (typeof window.openStatusConfirm === 'function') {
                    event.preventDefault();
                    window.openStatusConfirm(this);
                    return;
                }

                this.form.submit();
            });
        });

        modal.querySelector('[data-check-cancel]').addEventListener('click', function () { closeModal(true); });
        modal.addEventListener('click', function (event) { if (event.target === modal) closeModal(true); });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal(true);
        });
    })();
</script>
