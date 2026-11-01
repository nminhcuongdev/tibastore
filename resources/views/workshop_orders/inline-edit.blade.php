<script>
(function () {
    const catalog = @json($productCodes);
    const format = value => Number(value || 0).toLocaleString('vi-VN');
    function previewImage(row, product) {
        const cell = row.querySelector('[data-product-image]');
        cell.replaceChildren();
        if (product && product.image) {
            const button = document.createElement('button'); button.type = 'button'; button.className = 'thumb has-image'; button.dataset.fullImage = product.image;
            const img = document.createElement('img'); img.src = product.image; img.alt = product.code;
            button.appendChild(img); cell.appendChild(button);
        } else { const span = document.createElement('span'); span.className = 'thumb'; span.textContent = 'CHƯA CÓ ẢNH'; cell.appendChild(span); }
        row.querySelector('[data-product-label]').textContent = product ? product.name : 'Mã không còn trong kho';
    }
    function setupRow(row) {
        const codeInput = row.querySelector('[name=product_code]');
        const savedProduct = catalog.find(product => product.code.trim().toLowerCase() === codeInput.value.trim().toLowerCase());
        if (savedProduct) previewImage(row, savedProduct);
        row.querySelector('[data-remove-new-row]')?.addEventListener('click', () => {
            if (!row.hasAttribute('data-new-row')) return;
            row.remove();
            if (!document.querySelector('[data-new-row]')) appendNewRow();
        });
        function colorRow() { row.classList.toggle('is-arrived', row.querySelector('[name=status]').value === 'da_ve'); }
        colorRow();
        row.querySelector('[name=status]').addEventListener('change', colorRow);
        function resizeText(input) { input.style.height = 'auto'; input.style.height = input.scrollHeight + 'px'; }
        row.querySelectorAll('textarea').forEach(input => {
            input.addEventListener('input', () => resizeText(input));
            let width = -1;
            new ResizeObserver(entries => {
                const nextWidth = entries[0].contentRect.width;
                if (nextWidth !== width) { width = nextWidth; resizeText(input); }
            }).observe(input);
        });
        const form = row.querySelector('[data-inline-edit]');
        const state = row.querySelector('[data-save-state]');
        const button = form.querySelector('button');
        function changed(event) {
            if (!event.target.matches('input,textarea,select')) return;
            row.classList.add('has-changes'); state.textContent = 'Chưa lưu'; state.style.color = '';
            row.querySelector('[data-row-total]').textContent = format(Number(row.querySelector('[name=quantity]').value) * Number(row.querySelector('[name=unit_price]').value));
            if (event.target.name === 'product_code') {
                const code = event.target.value.trim().toLowerCase();
                previewImage(row, catalog.find(product => product.code.toLowerCase() === code));
            }
        }
        row.addEventListener('input', changed); row.addEventListener('change', changed);
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (!form.reportValidity()) return;
            const inputs = Array.from(row.querySelectorAll('input,textarea,select'));
            const body = new FormData(form);
            inputs.forEach(input => input.disabled = true); button.disabled = true; state.textContent = 'Đang lưu…';
            try {
                const response = await fetch(form.action, {method:'POST',body,credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
                const data = await response.json();
                if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Chưa lưu được.'));
                const isNew = row.hasAttribute('data-new-row');
                if (isNew) {
                    row.removeAttribute('data-new-row');
                    form.action = data.update_url;
                    const method = document.createElement('input'); method.type = 'hidden'; method.name = '_method'; method.value = 'PUT'; form.appendChild(method);
                    const deleteForm = document.createElement('form'); deleteForm.method = 'POST'; deleteForm.action = data.delete_url;
                    const token = document.createElement('input'); token.type = 'hidden'; token.name = '_token'; token.value = form.querySelector('[name=_token]').value;
                    const deleteMethod = document.createElement('input'); deleteMethod.type = 'hidden'; deleteMethod.name = '_method'; deleteMethod.value = 'DELETE';
                    const deleteButton = document.createElement('button'); deleteButton.type = 'submit'; deleteButton.className = 'danger'; deleteButton.textContent = 'Xóa dòng';
                    deleteForm.append(token, deleteMethod, deleteButton);
                    deleteForm.addEventListener('submit', event => { if (!confirm('Xóa dòng đặt hàng này?')) event.preventDefault(); });
                    row.querySelector('[data-delete-cell]').replaceChildren(deleteForm);
                    document.querySelectorAll('[data-workshop-rows] .empty').forEach(cell => cell.closest('tr').remove());
                    appendNewRow();
                }
                const summaryQuantity = document.querySelector('[data-summary-quantity]');
                const summaryAmount = document.querySelector('[data-summary-amount]');
                if (summaryQuantity) { const value = Number(summaryQuantity.dataset.summaryQuantity) + Number(data.quantity) - Number(row.dataset.quantity); summaryQuantity.dataset.summaryQuantity = value; summaryQuantity.textContent = format(value); }
                if (summaryAmount) { const value = Number(summaryAmount.dataset.summaryAmount) + Number(data.total) - Number(row.dataset.total); summaryAmount.dataset.summaryAmount = value; summaryAmount.textContent = format(value); }
                row.dataset.quantity = data.quantity; row.dataset.total = data.total;
                row.querySelector('[data-row-total]').textContent = format(data.total);
                row.querySelector('[name=status]').className = 'status-select st-' + row.querySelector('[name=status]').value;
                const selectedCode = row.querySelector('[name=product_code]').value;
                const selectedProduct = catalog.find(product => product.code.toLowerCase() === selectedCode.trim().toLowerCase());
                previewImage(row, {name:data.product_name,image:data.image || selectedProduct?.image,code:selectedCode});
                row.classList.remove('has-changes'); state.textContent = 'Đã lưu'; state.style.color = '';
            } catch (error) { state.textContent = 'Chưa lưu: ' + error.message; state.style.color = '#b42318'; }
            finally { inputs.forEach(input => input.disabled = false); button.disabled = false; }
        });
    }
    let newRowIndex = 0;
    function appendNewRow() {
        const template = document.getElementById('workshop-new-row');
        const fragment = document.createElement('template');
        fragment.innerHTML = template.innerHTML.replaceAll('__NEW__', String(++newRowIndex));
        const row = fragment.content.querySelector('tr');
        document.querySelector('[data-workshop-rows]').appendChild(row);
        setupRow(row);
        return row;
    }
    document.querySelectorAll('[data-edit-row]').forEach(setupRow);
    appendNewRow();
    document.querySelector('[data-add-inline-row]')?.addEventListener('click', () => {
        const row = appendNewRow();
        row.querySelector('[name=product_code]').focus();
    });
    window.addEventListener('beforeunload', event => { if (document.querySelector('[data-edit-row].has-changes')) { event.preventDefault(); event.returnValue = ''; } });
})();
</script>
