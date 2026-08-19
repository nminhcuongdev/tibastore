<style>
    .codes-modal {
        align-items: center;
        background: rgba(63, 39, 48, .72);
        display: none;
        inset: 0;
        justify-content: center;
        padding: 24px;
        position: fixed;
        z-index: 1100;
    }
    .codes-modal.is-open { display: flex; }
    .codes-modal__box {
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
    .codes-modal__title {
        color: #6f253f;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 24px;
        margin: 0 0 8px;
    }
    .codes-modal__hint {
        color: #8b6672;
        font-size: 13px;
        line-height: 1.5;
        margin: 0 0 16px;
    }
    .codes-modal__table-shell {
        border: 1px solid #f2d3dc;
        border-radius: 8px;
        overflow: hidden;
    }
    .codes-modal__table { border-collapse: collapse; width: 100%; }
    .codes-modal__table th,
    .codes-modal__table td {
        border-bottom: 1px solid #f7e3e9;
        padding: 10px 12px;
        text-align: left;
    }
    .codes-modal__table thead th {
        background: #fff0f4;
        color: #81304c;
        font-size: 12px;
        letter-spacing: .02em;
        text-transform: uppercase;
    }
    .codes-modal__table td.qty,
    .codes-modal__table th.qty { text-align: right; }
    .codes-modal__table tbody tr:last-child td { border-bottom: 0; }
    .codes-modal__table tfoot td {
        background: #fffafb;
        border-top: 1px solid #f2d3dc;
        font-weight: 800;
    }
    .codes-modal__code { color: #a13b60; font-weight: 800; }
    .codes-modal__table td.thumb-cell { width: 64px; }
    .codes-thumb {
        align-items: center;
        background: #f9e5ec;
        border: 1px solid #f1cbd7;
        border-radius: 8px;
        color: #a64465;
        cursor: default;
        display: flex;
        font-family: inherit;
        font-size: 9px;
        font-weight: 800;
        height: 52px;
        justify-content: center;
        overflow: hidden;
        padding: 0;
        position: relative;
        text-align: center;
        width: 52px;
    }
    .codes-thumb.has-image { cursor: zoom-in; }
    .codes-thumb img { height: 100%; object-fit: cover; transition: transform .18s ease; width: 100%; }
    .codes-thumb.has-image:hover,
    .codes-thumb.has-image:focus {
        border-color: #c9577d;
        box-shadow: 0 10px 24px rgba(117, 44, 69, .18);
        outline: none;
        overflow: visible;
        z-index: 20;
    }
    .codes-thumb.has-image:hover img,
    .codes-thumb.has-image:focus img {
        border-radius: 8px;
        transform: scale(2.4);
    }
    .codes-lightbox {
        align-items: center;
        background: rgba(63, 39, 48, .82);
        cursor: zoom-out;
        display: none;
        inset: 0;
        justify-content: center;
        padding: 24px;
        position: fixed;
        z-index: 1200;
    }
    .codes-lightbox.is-open { display: flex; }
    .codes-lightbox img {
        background: #fff;
        border: 8px solid #fff;
        border-radius: 8px;
        box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
        max-height: min(82vh, 760px);
        max-width: min(88vw, 760px);
        object-fit: contain;
    }
    .codes-modal__actions { display: flex; justify-content: flex-end; margin-top: 18px; }
</style>

<div class="codes-modal" data-codes-modal aria-hidden="true">
    <div class="codes-modal__box" role="dialog" aria-modal="true" aria-labelledby="codes-modal-title">
        <h2 id="codes-modal-title" class="codes-modal__title">Mã hàng: <span data-codes-order-name></span></h2>
        <p class="codes-modal__hint">Danh sách mã hàng và size trong đơn. Cùng một mã-size xuất hiện ở nhiều dòng giá sẽ được cộng gộp số lượng.</p>
        <div class="codes-modal__table-shell">
            <table class="codes-modal__table">
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Mã hàng</th>
                        <th>Tên hàng</th>
                        <th>Size</th>
                        <th class="qty">Số lượng</th>
                    </tr>
                </thead>
                <tbody data-codes-body></tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">Tổng số lượng</td>
                        <td class="qty" data-codes-total>0</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="codes-modal__actions">
            <button type="button" class="button secondary" data-codes-close>Đóng</button>
        </div>
    </div>
</div>

<div class="codes-lightbox" data-codes-lightbox aria-hidden="true">
    <img data-codes-lightbox-image src="" alt="">
</div>

<script>
    (function () {
        const modal = document.querySelector('[data-codes-modal]');
        if (!modal) return;

        const body = modal.querySelector('[data-codes-body]');
        const totalEl = modal.querySelector('[data-codes-total]');
        const orderNameEl = modal.querySelector('[data-codes-order-name]');

        function addCell(row, text, className) {
            const cell = document.createElement('td');
            if (className) cell.className = className;
            cell.textContent = text;
            row.appendChild(cell);
            return cell;
        }

        const lightbox = document.querySelector('[data-codes-lightbox]');
        const lightboxImage = document.querySelector('[data-codes-lightbox-image]');

        function openLightbox(url, alt) {
            lightboxImage.src = url;
            lightboxImage.alt = alt || 'Ảnh sản phẩm';
            lightbox.classList.add('is-open');
            lightbox.setAttribute('aria-hidden', 'false');
        }

        function closeLightbox() {
            lightbox.classList.remove('is-open');
            lightbox.setAttribute('aria-hidden', 'true');
            lightboxImage.src = '';
        }

        // O anh: co anh thi hover phong to tai cho, bam mo anh lon.
        function buildThumbCell(item) {
            const cell = document.createElement('td');
            cell.className = 'thumb-cell';

            if (!item.image) {
                const empty = document.createElement('div');
                empty.className = 'codes-thumb';
                empty.textContent = 'CHƯA CÓ ẢNH';
                cell.appendChild(empty);
                return cell;
            }

            const alt = item.code + (item.name ? ' - ' + item.name : '');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'codes-thumb has-image';
            button.setAttribute('aria-label', 'Phóng to ảnh ' + alt);

            const img = document.createElement('img');
            img.src = item.image;
            img.alt = alt;

            button.appendChild(img);
            button.addEventListener('click', function () { openLightbox(item.image, alt); });
            cell.appendChild(button);

            return cell;
        }

        function openModal(button) {
            let rows = [];
            try { rows = JSON.parse(button.dataset.codes || '[]'); } catch (e) { rows = []; }

            orderNameEl.textContent = button.dataset.orderName || '';
            body.innerHTML = '';

            let total = 0;

            rows.forEach(function (item) {
                const row = document.createElement('tr');
                row.appendChild(buildThumbCell(item));
                addCell(row, item.code, 'codes-modal__code');
                addCell(row, item.name || '—');
                addCell(row, item.size);
                addCell(row, Number(item.quantity).toLocaleString('vi-VN'), 'qty');
                body.appendChild(row);
                total += Number(item.quantity) || 0;
            });

            if (rows.length === 0) {
                const row = document.createElement('tr');
                const cell = addCell(row, 'Đơn chưa có mã hàng nào.');
                cell.colSpan = 5;
                body.appendChild(row);
            }

            totalEl.textContent = total.toLocaleString('vi-VN');
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            closeLightbox();
        }

        document.querySelectorAll('[data-codes]').forEach(function (button) {
            button.addEventListener('click', function () { openModal(this); });
        });

        modal.querySelector('[data-codes-close]').addEventListener('click', closeModal);
        modal.addEventListener('click', function (event) { if (event.target === modal) closeModal(); });
        lightbox.addEventListener('click', closeLightbox);

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;

            // Anh lon dang mo thi Esc chi dong anh, giu nguyen modal ben duoi.
            if (lightbox.classList.contains('is-open')) {
                closeLightbox();
                return;
            }

            if (modal.classList.contains('is-open')) closeModal();
        });
    })();
</script>
