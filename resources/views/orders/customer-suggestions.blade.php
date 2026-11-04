<style>
.customer-suggestions{position:fixed;z-index:1100;background:#fff;border:1px solid #ccd8ec;border-radius:10px;box-shadow:0 8px 24px #203b6026;padding:6px;max-height:300px;overflow:auto;color:#203b60}.customer-suggestions[hidden]{display:none}.customer-suggestions button{display:block;width:100%;border:0;border-radius:6px;background:white;text-align:left;padding:10px;font:inherit;color:inherit;cursor:pointer;white-space:normal;overflow-wrap:anywhere}.customer-suggestions button:hover,.customer-suggestions button:focus{background:#eef3ff;outline:2px solid #82a9d1;outline-offset:-2px}.customer-suggestions strong,.customer-suggestions span{display:block}.customer-suggestions span{font-size:13px;margin-top:4px;color:#52637c}.customer-suggestions p{font-size:12px;margin:5px 8px;color:#65758a}
</style>
<div id="customer-suggestions" class="customer-suggestions" hidden aria-label="Thông tin khách đã thuê"><p>Khách đã thuê — chọn để điền SĐT và địa chỉ</p><div data-customer-results></div></div>
<script>
(() => {
    const fields = ['order_name', 'phone', 'address'].map(id => document.getElementById(id));
    const popup = document.getElementById('customer-suggestions');
    const results = popup.querySelector('[data-customer-results]');
    const sourceInput = document.getElementById('source');
    let sourceEdited = @json(old('source') !== null);
    sourceInput.addEventListener('change', event => { if (event.isTrusted) sourceEdited = true; });
    function fillPreviousSource(customer) {
        if (!@json($mode === 'create') || sourceEdited || !customer.source) return;
        if (!Array.from(sourceInput.options).some(option => option.value === customer.source)) return;
        sourceInput.value = customer.source;
        sourceInput.dispatchEvent(new Event('change', {bubbles:true}));
    }
    let active = null, timer, controller, revision = 0;
    fields.forEach(input => { input.autocomplete = 'off'; input.setAttribute('aria-controls', popup.id); input.setAttribute('aria-expanded', 'false'); });
    function close() {
        clearTimeout(timer); revision++; controller?.abort(); popup.hidden = true;
        active?.setAttribute('aria-expanded', 'false'); active = null;
    }
    function position() {
        if (!active || popup.hidden) return;
        const rect = active.getBoundingClientRect();
        popup.style.width = Math.min(Math.max(rect.width, 320), window.innerWidth - 24) + 'px';
        popup.style.left = Math.max(12, Math.min(rect.left, window.innerWidth - popup.offsetWidth - 12)) + 'px';
        const below = window.innerHeight - rect.bottom - 12;
        const above = below < 180 && rect.top > below;
        popup.style.maxHeight = Math.max(100, Math.min(300, (above ? rect.top : below) - 12)) + 'px';
        popup.style.top = Math.max(12, above ? rect.top - popup.offsetHeight - 6 : rect.bottom + 6) + 'px';
    }
    function search(input) {
        close(); active = input;
        const value = input.value.trim();
        const field = value ? input.id : 'order_name';
        const query = value || fields[0].value.trim();
        if (query.length < 2) return;
        const ticket = revision;
        timer = setTimeout(async () => {
            controller = new AbortController();
            try {
                const url = new URL(@json(route('orders.customer-suggestions')));
                url.searchParams.set('field', field); url.searchParams.set('q', query);
                if (@json($order->id)) url.searchParams.set('exclude_order_id', @json($order->id));
                const response = await fetch(url, {signal:controller.signal, credentials:'same-origin', headers:{Accept:'application/json'}});
                if (!response.ok) return;
                const customers = await response.json();
                if (ticket !== revision || active !== input) return;
                const normalizeName = value => String(value || '').trim().toLowerCase().replace(/\s+/g, ' ');
                const exact = customers.filter(customer => field === 'phone'
                    ? String(customer.phone || '').replace(/\D/g, '') === query.replace(/\D/g, '')
                    : field === 'order_name' && normalizeName(customer.order_name) === normalizeName(query));
                const identities = new Set(exact.map(customer => String(customer.phone || '').replace(/\D/g, '') || normalizeName(customer.order_name)));
                if (exact.length && identities.size === 1) fillPreviousSource(exact[0]);
                results.replaceChildren();
                customers.forEach(customer => {
                    const button = document.createElement('button'); button.type = 'button';
                    const name = document.createElement('strong'); name.textContent = customer.order_name || 'Khách đã thuê';
                    const phone = document.createElement('span'); phone.textContent = 'SĐT: ' + (customer.phone || 'Chưa có');
                    const address = document.createElement('span'); address.textContent = 'Địa chỉ: ' + (customer.address || 'Chưa có');
                    button.append(name, phone, address);
                    if (customer.source_label) {
                        const previousSource = document.createElement('span'); previousSource.textContent = 'Nguồn lần gần nhất: ' + customer.source_label;
                        button.appendChild(previousSource);
                    }
                    button.addEventListener('click', () => {
                        const source = active;
                        close();
                        fillPreviousSource(customer);
                        ['phone', 'address'].forEach(id => {
                            if (!customer[id]) return;
                            const target = document.getElementById(id); target.value = customer[id];
                            target.dispatchEvent(new Event('input', {bubbles:true}));
                            target.dispatchEvent(new Event('change', {bubbles:true}));
                        });
                        close(); source?.focus({preventScroll:true}); close();
                    });
                    results.appendChild(button);
                });
                popup.hidden = !customers.length;
                input.setAttribute('aria-expanded', String(customers.length > 0)); position();
            } catch (error) { if (error.name !== 'AbortError') close(); }
        }, 250);
    }
    fields.forEach(input => {
        input.addEventListener('focus', () => search(input));
        input.addEventListener('input', event => { if (event.isTrusted) search(input); });
        input.addEventListener('keydown', event => {
            if (event.key === 'ArrowDown' && !popup.hidden) { event.preventDefault(); results.querySelector('button')?.focus(); }
            if (event.key === 'Escape') close();
        });
    });
    popup.addEventListener('keydown', event => {
        const buttons = Array.from(results.children), index = buttons.indexOf(document.activeElement);
        if (event.key === 'Escape') { const input = active; close(); input?.focus(); close(); }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault(); buttons[(index + (event.key === 'ArrowDown' ? 1 : -1) + buttons.length) % buttons.length]?.focus();
        }
    });
    document.addEventListener('pointerdown', event => { if (!popup.contains(event.target) && !fields.includes(event.target)) close(); });
    document.addEventListener('focusin', event => { if (!popup.contains(event.target) && !fields.includes(event.target)) close(); });
    window.addEventListener('resize', close);
    window.addEventListener('scroll', event => { if (!popup.contains(event.target)) close(); }, true);
})();
</script>
