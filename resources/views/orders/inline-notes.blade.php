<script>
(() => {
    const pending = new Set();
    document.querySelectorAll('[data-inline-order-note]').forEach(input => {
        const state = input.parentElement.querySelector('[data-note-state]');
        let saved = input.value, timer, saving = false;
        async function save() {
            if (saving) return;
            if (input.value === saved) { pending.delete(input); state.textContent = 'Đã lưu'; return; }
            saving = true; const value = input.value; state.textContent = 'Đang lưu…';
            try {
                const response = await fetch(input.dataset.url, {method:'PATCH', credentials:'same-origin',
                    headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':@json(csrf_token())}, body:JSON.stringify({note:value})});
                const data = await response.json();
                if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : data.message);
                saved = value;
                if (input.value === saved) { pending.delete(input); state.textContent = 'Đã lưu'; }
            } catch(error) { state.textContent = 'Chưa lưu: ' + error.message; }
            finally { saving = false; if (input.value !== value) { clearTimeout(timer); timer = setTimeout(save, 650); } }
        }
        input.addEventListener('input', () => { pending.add(input); state.textContent = 'Chờ tự lưu…'; clearTimeout(timer); timer = setTimeout(save, 650); });
        input.addEventListener('blur', () => { clearTimeout(timer); save(); });
    });
    document.addEventListener('personal-history-request', event => {
        if (pending.size) { event.preventDefault(); document.querySelector('[data-personal-state]').textContent = 'Vui lòng chờ ghi chú tự lưu xong.'; }
    });
    window.addEventListener('beforeunload', event => { if (pending.size) { event.preventDefault(); event.returnValue = ''; } });
})();
</script>
