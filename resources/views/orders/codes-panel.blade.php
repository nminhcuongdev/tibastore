{{-- Đóng/mở hàng "sổ mã hàng" của đơn và xem ảnh lớn.
     Dữ liệu đã được render sẵn trong bảng nên mở là thấy ngay, không gọi mạng. --}}
<div class="codes-zoom" data-codes-zoom aria-hidden="true">
    <img data-codes-zoom-image src="" alt="">
</div>

<script>
    (function () {
        const STORAGE_KEY = 'tiba_orders_codes_open';
        const toggleAll = document.querySelector('[data-codes-toggle-all]');
        const toggleAllLabel = document.querySelector('[data-codes-toggle-all-label]');
        const panels = Array.from(document.querySelectorAll('[data-codes-panel]'));

        function panelFor(orderId) {
            return document.querySelector('[data-codes-panel="' + orderId + '"]');
        }

        function buttonFor(orderId) {
            return document.querySelector('[data-codes-toggle="' + orderId + '"]');
        }

        function setOpen(orderId, open) {
            const panel = panelFor(orderId);
            const button = buttonFor(orderId);
            if (!panel) return;

            panel.hidden = !open;
            if (button) button.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function isOpen(panel) {
            return !panel.hidden;
        }

        function syncToggleAll() {
            const allOpen = panels.length > 0 && panels.every(isOpen);
            if (!toggleAll) return;

            toggleAll.setAttribute('aria-expanded', allOpen ? 'true' : 'false');
            if (toggleAllLabel) {
                toggleAllLabel.textContent = allOpen ? 'Thu tất cả mã hàng' : 'Sổ tất cả mã hàng';
            }
        }

        // Nho lua chon "so tat ca" de sang trang khac van giu nguyen kieu xem.
        function readPreference() {
            try {
                return localStorage.getItem(STORAGE_KEY) === '1';
            } catch (error) {
                return false;
            }
        }

        function writePreference(value) {
            try {
                localStorage.setItem(STORAGE_KEY, value ? '1' : '0');
            } catch (error) {
                // Trinh duyet chan luu tru thi bo qua, chuc nang van chay.
            }
        }

        document.querySelectorAll('[data-codes-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const orderId = this.dataset.codesToggle;
                const panel = panelFor(orderId);
                if (!panel) return;

                setOpen(orderId, panel.hidden);
                syncToggleAll();
            });
        });

        if (toggleAll) {
            toggleAll.addEventListener('click', function () {
                const open = this.getAttribute('aria-expanded') !== 'true';

                panels.forEach(function (panel) {
                    setOpen(panel.dataset.codesPanel, open);
                });

                writePreference(open);
                syncToggleAll();
            });
        }

        if (readPreference()) {
            panels.forEach(function (panel) {
                setOpen(panel.dataset.codesPanel, true);
            });
        }

        syncToggleAll();

        // ---- Anh lon ----
        const zoom = document.querySelector('[data-codes-zoom]');
        const zoomImage = document.querySelector('[data-codes-zoom-image]');

        function closeZoom() {
            zoom.classList.remove('is-open');
            zoom.setAttribute('aria-hidden', 'true');
            zoomImage.src = '';
        }

        document.querySelectorAll('.code-thumb.has-image').forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                zoomImage.src = this.dataset.zoomSrc;
                zoomImage.alt = this.dataset.zoomAlt || 'Ảnh sản phẩm';
                zoom.classList.add('is-open');
                zoom.setAttribute('aria-hidden', 'false');
            });
        });

        zoom.addEventListener('click', closeZoom);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && zoom.classList.contains('is-open')) {
                closeZoom();
            }
        });
    })();
</script>
