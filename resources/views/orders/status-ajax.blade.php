{{-- Đổi trạng thái đơn ngay tại danh sách, không tải lại trang.
     Chỉ vẽ lại đúng những ô đổi theo: thẻ trạng thái, màu nền dòng, tổng đơn
     và còn lại. Trang nào không nhúng partial này thì form vẫn submit như cũ. --}}
<script>
    (function () {
        const flashArea = document.querySelector('[data-flash-area]');

        function flash(message, isError) {
            if (!flashArea) return;

            flashArea.innerHTML = '';

            const box = document.createElement('div');
            box.className = 'status' + (isError ? ' is-error' : '');
            box.textContent = message;
            flashArea.appendChild(box);

            // Thong bao loi giu lai de con doc; bao thanh cong thi tu tat.
            if (!isError) {
                setTimeout(function () {
                    if (box.isConnected) box.remove();
                }, 4000);
            }

            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function messagesFrom(payload) {
            if (payload && payload.errors) {
                const all = [];
                Object.keys(payload.errors).forEach(function (key) {
                    [].concat(payload.errors[key]).forEach(function (line) { all.push(line); });
                });

                if (all.length > 0) return all.join(' ');
            }

            return (payload && payload.message) || 'Không cập nhật được trạng thái. Vui lòng thử lại.';
        }

        function applyOrder(order) {
            const select = document.querySelector('select.status-select[data-order-id="' + order.id + '"]');
            const row = document.querySelector('[data-order-row="' + order.id + '"]');
            const panel = document.querySelector('[data-codes-panel="' + order.id + '"]');

            if (select) {
                select.value = order.status;
                select.dataset.current = order.status;
                select.dataset.currentlyOut = order.currently_out ? '1' : '0';
                select.className = 'status-select status-' + order.color_key;
            }

            [row, panel].forEach(function (el) {
                if (!el) return;
                // Bo mau trang thai cu roi to mau moi.
                el.className = el.className.replace(/\brow-\S+/g, '').trim();
                el.classList.add('row-' + order.color_key);
            });

            if (row) {
                const total = row.querySelector('[data-cell="total"]');
                const remaining = row.querySelector('[data-cell="remaining"]');

                // Kiem don co the sinh tien boi thuong, keo theo tong don va con lai.
                if (total) total.textContent = order.total_with_compensation;
                if (remaining) remaining.textContent = order.remaining;
            }

            // So ton va so nhan lai trong cache modal khong con dung nua.
            if (typeof window.clearOrderModalData === 'function') {
                window.clearOrderModalData(order.id);
            }
        }

        // Tra ve Promise de noi mo goi biet luc nao xong; loi mang thi bao
        // that chu khong submit ngam de trang khoi nhay lung tung.
        window.submitOrderStatus = function (form, orderId) {
            const body = new FormData(form);
            const selectInRow = orderId
                ? document.querySelector('select.status-select[data-order-id="' + orderId + '"]')
                : form.querySelector('select[name="status"]');

            // Modal kiem don dung form rieng nen phai vá lai select cua dong cho khop.
            if (selectInRow && !form.contains(selectInRow)) {
                selectInRow.value = body.get('status');
            }

            return fetch(form.getAttribute('action'), {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (payload) {
                    if (!response.ok) {
                        const error = new Error(messagesFrom(payload));
                        error.handled = true;
                        throw error;
                    }

                    return payload;
                });
            }).then(function (payload) {
                if (payload.order) applyOrder(payload.order);
                flash(payload.message || 'Đã cập nhật trạng thái đơn hàng.', false);
            }).catch(function (error) {
                if (selectInRow && selectInRow.dataset.current) {
                    selectInRow.value = selectInRow.dataset.current;
                }

                flash(error.handled ? error.message : 'Mất kết nối tới máy chủ, trạng thái chưa được đổi.', true);
            });
        };
    })();
</script>
