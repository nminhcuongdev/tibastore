<style>
.click-copy{cursor:copy;border-radius:3px}.click-copy:hover{text-decoration:underline;text-underline-offset:3px}.click-copy:focus-visible{outline:2px solid #73a8cc;outline-offset:3px}.copy-feedback{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:10000;background:#203b60;color:#fff;box-shadow:0 6px 24px #203b6033;border-radius:9px;padding:12px 18px;font:13px Arial,sans-serif;pointer-events:none;max-width:90vw;white-space:normal;overflow-wrap:anywhere}@media print{.copy-feedback{display:none!important}.click-copy{text-decoration:none!important}}
</style>
<script>
(function () {
    if (window.tibaClickCopyReady) return;
    window.tibaClickCopyReady = true;
    let timer;
    function feedback(message) {
        let toast = document.querySelector('[data-copy-feedback]');
        if (!toast) { toast = document.createElement('div'); toast.className = 'copy-feedback'; toast.dataset.copyFeedback = ''; toast.setAttribute('role', 'status'); toast.setAttribute('aria-live', 'polite'); document.body.appendChild(toast); }
        toast.textContent = message; toast.hidden = false;
        clearTimeout(timer); timer = setTimeout(() => { toast.hidden = true; }, 2200);
    }
    async function copy(value) {
        if (!value || !value.trim() || value === 'N/A' || value === '—') return;
        try {
            if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(value);
            else {
                const focused = document.activeElement;
                const start = focused && focused.selectionStart;
                const end = focused && focused.selectionEnd;
                const input = document.createElement('textarea'); input.value = value; input.style.cssText = 'position:fixed;opacity:0;pointer-events:none'; document.body.appendChild(input);
                try { input.select(); if (!document.execCommand('copy')) throw new Error('Copy failed'); }
                finally { input.remove(); if (focused && focused.focus) { focused.focus({preventScroll:true}); if (start !== null && start !== undefined && focused.setSelectionRange) { try { focused.setSelectionRange(start, end); } catch (error) {} } } }
            }
            feedback('Đã sao chép: ' + value);
        } catch (error) { feedback('Chưa sao chép được. Bạn vui lòng thử lại.'); }
    }
    function act(event) {
        const target = event.target;
        if (!(target instanceof Element)) return;
        // Existing copy controls keep their current handlers.
        if (target.closest('[data-copy-order-name],[data-copy-code]')) return;
        const explicit = target.closest('[data-copy-value]');
        if (explicit) { if (explicit.closest('a')) event.preventDefault(); copy(explicit.dataset.copyValue); return; }
        if (event.type === 'keydown') return;
        if (target.matches('input[name="order_name"],textarea[name="order_name"],input[name="product_code"],input[data-field="code"]')) { copy(target.value); return; }
        if (target.matches('input[data-search]')) { const block = target.closest('.order-item'); if (block && block.dataset.productCode) copy(block.dataset.productCode); return; }
        if (!location.pathname.startsWith('/users') && target.matches('input[name="code"]')) { copy(target.value); return; }
        const code = target.closest('.order-invoice-code,.product-code,.picker-description>strong,.codes-modal__code');
        if (code) copy(code.textContent.trim());
    }
    document.addEventListener('click', act);
    document.addEventListener('keydown', event => {
        if ((event.key === 'Enter' || event.key === ' ') && event.target.matches('[data-copy-value]')) { event.preventDefault(); act(event); }
    });
})();
</script>
