<script>
    (function () {
        const table = document.querySelector('.orders-scroll');
        if (!table) return;
        const key = 'orders:list-position:' + location.pathname + location.search;

        function save() {
            try {
                sessionStorage.setItem(key, JSON.stringify({
                    x: window.scrollX, y: window.scrollY,
                    left: table.scrollLeft, top: table.scrollTop,
                }));
            } catch (error) { /* Storage may be disabled. */ }
        }

        try {
            const saved = JSON.parse(sessionStorage.getItem(key) || 'null');
            if (saved) {
                table.scrollLeft = saved.left;
                table.scrollTop = saved.top;
                window.scrollTo({ left: saved.x, top: saved.y, behavior: 'instant' });
            }
        } catch (error) { /* Ignore unavailable or invalid stored positions. */ }

        table.addEventListener('scroll', save, { passive: true });
        window.addEventListener('scroll', save, { passive: true });
        window.addEventListener('pagehide', save);
        document.addEventListener('click', function (event) {
            if (event.target.closest('a, button')) save();
        }, true);
        document.addEventListener('submit', save, true);
    })();
</script>
