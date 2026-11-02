<span class="personal-history no-print" data-personal-history="{{ $historySection }}" style="display:inline-flex;gap:10px;align-items:center;flex-wrap:wrap">
    <button type="button" class="button secondary" data-personal-action="undo" style="background:white;color:#384cc9;border:1px solid #c7d3ed;border-radius:10px;padding:12px 18px;font:inherit;font-weight:700;cursor:pointer">↶ Hoàn tác</button>
    <button type="button" class="button secondary" data-personal-action="redo" style="background:white;color:#384cc9;border:1px solid #c7d3ed;border-radius:10px;padding:12px 18px;font:inherit;font-weight:700;cursor:pointer">↷ Làm lại</button>
    <span data-personal-state role="status" style="font-size:13px"></span>
</span>
@once
<script>
document.addEventListener('DOMContentLoaded', () => {
    const key = 'personal-history-scroll:' + location.pathname + location.search;
    const stored = sessionStorage.getItem(key);
    if (stored) {
        sessionStorage.removeItem(key);
        requestAnimationFrame(() => requestAnimationFrame(() => {
            const position = JSON.parse(stored);
            window.scrollTo(position.x, position.y);
            document.querySelectorAll('[data-history-scroll], .table-wrap, .table-shell').forEach((el, i) => {
                el.scrollTop = position.tables[i]?.y || 0; el.scrollLeft = position.tables[i]?.x || 0;
            });
        }));
    }
    document.querySelectorAll('[data-personal-history]').forEach(group => {
        let pending = false;
        group.addEventListener('click', async event => {
            const button = event.target.closest('[data-personal-action]');
            if (!button || pending) return;
            const local = new CustomEvent('personal-history-request', {cancelable:true, detail:{direction:button.dataset.personalAction}});
            if (!document.dispatchEvent(local)) return;
            pending = true;
            const state = group.querySelector('[data-personal-state]');
            state.textContent = button.dataset.personalAction === 'redo' ? 'Đang làm lại…' : 'Đang hoàn tác…';
            const controls = Array.from(document.querySelectorAll('button,input,select,textarea')).filter(el => !el.disabled);
            controls.forEach(el => el.disabled = true);
            try {
                const response = await fetch(@json(url('/personal-edits')) + '/' + group.dataset.personalHistory + '/' + button.dataset.personalAction, {
                    method:'POST', credentials:'same-origin', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':@json(csrf_token())}, body:'{}'
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : data.message);
                sessionStorage.setItem(key, JSON.stringify({x:window.scrollX,y:window.scrollY,tables:
                    Array.from(document.querySelectorAll('[data-history-scroll], .table-wrap, .table-shell')).map(el => ({x:el.scrollLeft,y:el.scrollTop}))}));
                document.dispatchEvent(new Event('personal-history-reload'));
                if (data.redirect) location.assign(data.redirect); else location.reload();
            } catch (error) {
                state.textContent = error.message || 'Chưa thực hiện được. Vui lòng thử lại.';
                pending = false;
                controls.forEach(el => el.disabled = false);
            }
        });
    });
});
</script>
@endonce
