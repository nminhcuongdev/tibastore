<script>
(function () {
    const list = new URL(@json(route('orders.index')));
    const current = new URL(location.href);
    const isList = current.pathname === list.pathname;
    const table = document.querySelector('.orders-scroll');
    const prefix = 'orders:position:v2:';
    let restoring = true;
    let leaving = false;
    function read(key) {
        try { return JSON.parse(sessionStorage.getItem(key) || 'null'); }
        catch (error) { return null; }
    }
    function write(key, value) {
        try { sessionStorage.setItem(key, JSON.stringify(value)); }
        catch (error) { /* Storage may be disabled. */ }
    }
    // Redirects can change query parameter order without changing the filters.
    const params = new URLSearchParams();
    current.searchParams.forEach(function (value, name) {
        // HTML submits status[]; PHP redirects may serialize it as status[0].
        params.append(name.replace(/\[\d*\]/g, '[]'), value);
    });
    params.sort();
    const key = prefix + current.pathname + '?' + params.toString();
    function snapshot() {
        const filter = document.getElementById('filter-panel');
        return {
            x: window.scrollX, y: window.scrollY,
            left: table ? table.scrollLeft : 0, top: table ? table.scrollTop : 0,
            filterOpen: filter ? filter.classList.contains('is-open') : false,
            panels: Array.from(document.querySelectorAll('[data-codes-panel]:not([hidden])')).map(p => p.dataset.codesPanel),
        };
    }
    function save() {
        if (!restoring && !leaving) write(key, snapshot());
    }
    let saved = read(key);
    const pending = read(prefix + 'pending');
    if (isList && pending && pending.path === current.pathname) {
        saved = pending.position;
        write(prefix + 'pending', null);
    }
    if (isList) write(prefix + 'list', current.href);
    function restore() {
        if (!saved) return;
        const filter = document.getElementById('filter-panel');
        if (filter) {
            filter.classList.toggle('is-open', saved.filterOpen);
            const toggle = document.querySelector('[data-filter-toggle]');
            if (toggle) toggle.setAttribute('aria-expanded', String(saved.filterOpen));
        }
        document.querySelectorAll('[data-codes-panel]').forEach(function (panel) {
            const open = (saved.panels || []).includes(panel.dataset.codesPanel);
            panel.hidden = !open;
            const button = document.querySelector('[data-codes-toggle="' + panel.dataset.codesPanel + '"]');
            if (button) button.setAttribute('aria-expanded', String(open));
        });
        const all = document.querySelector('[data-codes-toggle-all]');
        if (all) {
            const panels = Array.from(document.querySelectorAll('[data-codes-panel]'));
            const open = panels.length > 0 && panels.every(p => !p.hidden);
            all.setAttribute('aria-expanded', String(open));
            const label = all.querySelector('[data-codes-toggle-all-label]');
            if (label) label.textContent = open ? 'Gom hết tất cả' : 'Sổ hết tất cả';
        }
        if (table) { table.scrollLeft = saved.left; table.scrollTop = saved.top; }
        window.scrollTo({ left: saved.x, top: saved.y, behavior: 'instant' });
    }
    // Restore after expansion scripts, images and column sizing have settled.
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
    function ready() {
        restore();
        requestAnimationFrame(function () {
            restore();
            requestAnimationFrame(function () { restore(); restoring = false; });
        });
    }
    if (document.readyState === 'complete') ready();
    else window.addEventListener('load', ready, { once: true });
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) { restoring = false; leaving = false; }
    });
    if (table) table.addEventListener('scroll', save, { passive: true });
    window.addEventListener('scroll', save, { passive: true });
    window.addEventListener('pagehide', save);
    document.addEventListener('click', function (event) {
        save();
        const link = event.target.closest('a[href]');
        if (!link) return;
        const target = new URL(link.href, current);
        if (isList && target.origin === current.origin && target.pathname === list.pathname) {
            write(prefix + 'pending', { path: target.pathname, position: snapshot() });
        }
        if (!event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey &&
            (!link.target || link.target === '_self') && !link.hash && !event.defaultPrevented) {
            leaving = true;
        }
    }, true);
    document.addEventListener('submit', function (event) {
        save();
        if (isList && event.target.id === 'filter-panel') {
            write(prefix + 'pending', { path: current.pathname, position: snapshot() });
        }
        // Preserve submitted position through unload-time scroll resets.
        if (!event.defaultPrevented) leaving = true;
    });
    // Carry list filters through detail/edit/create links and back to the list.
    const context = isList ? current.href : read(prefix + 'list');
    const query = current.searchParams.get('return_query') || (context ? new URL(context).search.slice(1) : '');
    document.querySelectorAll('a[href]').forEach(function (link) {
        const url = new URL(link.href, current);
        if (url.origin !== current.origin) return;
        if (!isList && url.pathname === list.pathname && !url.search) {
            url.search = query;
            link.href = url.href;
        } else if (url.pathname.startsWith(list.pathname + '/') &&
            (url.pathname.endsWith('/edit') || url.pathname.endsWith('/create') || /^\/\d+$/.test(url.pathname.slice(list.pathname.length)))) {
            url.searchParams.set('return_query', query);
            link.href = url.href;
        }
    });
    const input = document.querySelector('input[name="return_query"]');
    if (input && !input.value) input.value = query;
})();
</script>
