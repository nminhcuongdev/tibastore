<script>
(function () {
    const catalog = @json($productCodes);
    const format = value => Number(value || 0).toLocaleString('vi-VN');
    function editableLink(input, getUrl, editLabel) {
        const controls = document.createElement('div'); controls.className = 'workshop-value-link';
        const link = document.createElement('a'); link.target = '_blank'; link.rel = 'noopener';
        link.dataset.fieldLink = input.name;
        const edit = document.createElement('button'); edit.type = 'button'; edit.textContent = 'Sửa';
        edit.setAttribute('aria-label', editLabel);
        controls.append(link, edit); input.before(controls);
        function refresh() {
            const url = getUrl();
            link.textContent = input.value;
            if (url) link.href = url; else link.removeAttribute('href');
            if (document.activeElement !== input) {
                const show = !!url && !!input.value.trim();
                controls.hidden = !show; input.hidden = show;
            }
        }
        edit.addEventListener('click', () => {
            controls.hidden = true; input.hidden = false;
            input.focus({preventScroll:true}); input.select();
        });
        input.addEventListener('blur', refresh);
        input.addEventListener('input', () => { link.textContent = input.value; });
        refresh();
        return refresh;
    }
    function applyDuplicateWarnings(duplicates) {
        document.querySelectorAll('[data-edit-row][data-order-id]').forEach(row => {
            const peers = duplicates[row.dataset.orderId] || [];
            row.classList.toggle('is-duplicate', peers.length > 0);
            let warning = row.querySelector('[data-duplicate-warning]');
            if (!warning) {
                warning = document.createElement('span'); warning.dataset.duplicateWarning = '';
                warning.setAttribute('role', 'alert'); row.querySelector('td').appendChild(warning);
            }
            warning.hidden = !peers.length;
            warning.textContent = peers.length ? '⚠ Nghi trùng Tên đơn và Mã hàng với dòng #' + peers.join(', #') + '. Kiểm tra để tránh od hai lần.' : '';
        });
    }
    const scrollKey = 'workshop-scroll:' + location.pathname + location.search;
    function rememberScroll() {
        sessionStorage.setItem(scrollKey, JSON.stringify({x: window.scrollX, y: window.scrollY,
            tables: Array.from(document.querySelectorAll('.table-shell')).map(el => el.scrollLeft)}));
    }
    const previousScroll = sessionStorage.getItem(scrollKey);
    if (previousScroll) {
        sessionStorage.removeItem(scrollKey);
        requestAnimationFrame(() => requestAnimationFrame(() => {
            const position = JSON.parse(previousScroll);
            window.scrollTo(position.x, position.y);
            document.querySelectorAll('.table-shell').forEach((el, i) => el.scrollLeft = position.tables[i] || 0);
        }));
    }
    document.querySelectorAll('[data-undo-edit], [data-redo-edit]').forEach(historyButton => historyButton.addEventListener('click', async event => {
        const redo = event.currentTarget.hasAttribute('data-redo-edit');
        const state = document.querySelector('[data-undo-state]');
        if (document.querySelector('[data-edit-row].has-changes')) {
            state.textContent = 'Vui lòng chờ các thay đổi tự lưu xong.';
            return;
        }
        document.querySelectorAll('[data-undo-edit], [data-redo-edit]').forEach(control => control.disabled = true);
        // Freeze editing while undo is in flight so new auto-saves cannot race the reload.
        const controls = Array.from(document.querySelectorAll('[data-edit-row] input, [data-edit-row] textarea, [data-edit-row] select, [data-edit-row] button, [data-add-inline-row]'));
        const enabled = controls.filter(control => !control.disabled);
        enabled.forEach(control => control.disabled = true);
        document.querySelectorAll('[data-edit-row]').forEach(row => row.inert = true);
        state.textContent = redo ? 'Đang làm lại…' : 'Đang hoàn tác…';
        try {
            const body = new FormData();
            body.append('_token', document.querySelector('[data-inline-edit] [name=_token]').value);
            const url = redo ? @json(route('workshop-orders.redo-last-edit')) : @json(route('workshop-orders.undo-last-edit'));
            const response = await fetch(url, {
                method: 'POST', body, credentials: 'same-origin', headers: {Accept: 'application/json'}
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : data.message);
            rememberScroll();
            location.reload();
        } catch (error) {
            state.textContent = error.message || 'Chưa hoàn tác được. Vui lòng thử lại.';
            document.querySelectorAll('[data-undo-edit], [data-redo-edit]').forEach(control => control.disabled = false);
            enabled.forEach(control => control.disabled = false);
            document.querySelectorAll('[data-edit-row]').forEach(row => row.inert = false);
        }
    }));
    document.addEventListener('submit', event => {
        if (event.defaultPrevented || !event.target.querySelector('[name=_method][value=DELETE]')) return;
        rememberScroll();
    });
    function previewImage(row, product) {
        const cell = row.querySelector('[data-image-preview]');
        cell.replaceChildren();
        if (product && product.image) {
            const button = document.createElement('button'); button.type = 'button'; button.className = 'thumb has-image'; button.dataset.fullImage = product.image;
            const img = document.createElement('img'); img.src = product.image; img.alt = product.code;
            button.appendChild(img); cell.appendChild(button);
        } else { const span = document.createElement('span'); span.className = 'thumb'; span.textContent = 'CHƯA CÓ ẢNH'; cell.appendChild(span); }
        row.querySelector('[data-product-label]').textContent = product ? product.name : (row.querySelector('[name=product_code]').value === @json(\App\Models\WorkshopOrder::NO_PRODUCT_CODE) ? 'Chưa tạo mã' : 'Chọn mã để hiện tên và ảnh');
    }
    function setupRow(row) {
        if (row.hasAttribute('data-new-row')) {
            const parts = new Intl.DateTimeFormat('en-US', {timeZone:'Asia/Ho_Chi_Minh', year:'numeric', month:'2-digit', day:'2-digit'}).formatToParts(new Date());
            const part = type => parts.find(item => item.type === type).value;
            row.querySelector('[name=ordered_date]').value = `${part('year')}-${part('month')}-${part('day')}`;
        }
        const codeInput = row.querySelector('[name=product_code]');
        const refreshCodeLink = editableLink(codeInput, () => {
            const product = catalog.find(item => item.code.trim().toLowerCase() === codeInput.value.trim().toLowerCase());
            return product?.sizes?.length ? @json(route('products.show', ['product' => '__PRODUCT__'])).replace('__PRODUCT__', product.sizes[0].id)
                : (row.dataset.warehouseCode === codeInput.value ? row.dataset.warehouseUrl : null);
        }, 'Sửa mã hàng');
        const nameInput = row.querySelector('[name=order_name]');
        const refreshNameLink = editableLink(nameInput, () => {
            if (row.dataset.customerOrderId) return row.dataset.orderUrl;
            if (!nameInput.value.trim()) return null;
            const url = new URL(@json(route('orders.index')));
            url.searchParams.set('order_name', nameInput.value.trim()); return url.href;
        }, 'Sửa tên đơn');
        const imageCell = row.querySelector('[data-product-image]');
        const preview = document.createElement('div'); preview.dataset.imagePreview = '';
        preview.append(...imageCell.childNodes); imageCell.appendChild(preview);
        const savedImage = preview.querySelector('img')?.src;
        let customImage = row.dataset.customImage || null;
        let pendingImage = null;
        let objectUrl = null;
        imageCell.tabIndex = 0;
        imageCell.setAttribute('aria-label', 'Ảnh đặt hàng. Bấm vào ô rồi nhấn Ctrl+V để dán ảnh.');
        const upload = document.createElement('input'); upload.type = 'file'; upload.accept = 'image/jpeg,image/png,image/gif,image/webp'; upload.hidden = true;
        const uploadButton = document.createElement('button'); uploadButton.type = 'button'; uploadButton.textContent = 'Chọn ảnh';
        const hint = document.createElement('div'); hint.className = 'muted'; hint.textContent = 'Bấm ô ảnh rồi Ctrl+V · tối đa 5MB';
        imageCell.append(upload, uploadButton, hint);
        uploadButton.addEventListener('click', () => upload.click());
        function acceptImage(file) {
            if (!file) return;
            if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
                state.textContent = 'Chọn ảnh JPG, PNG, GIF hoặc WebP tối đa 5MB.'; state.style.color = '#b42318'; return;
            }
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            pendingImage = file; objectUrl = URL.createObjectURL(file); customImage = objectUrl;
            previewImage(row, {image:customImage, code:codeInput.value, name:row.querySelector('[data-product-label]').textContent});
            row.classList.add('has-changes'); state.textContent = 'Chờ tự lưu ảnh…'; scheduleSave(); state.style.color = '';
        }
        upload.addEventListener('change', () => acceptImage(upload.files[0]));
        row.addEventListener('paste', event => {
            const image = Array.from(event.clipboardData?.items || []).find(item => item.type.startsWith('image/'));
            if (image) { event.preventDefault(); acceptImage(image.getAsFile()); }
        });
        const savedProduct = catalog.find(product => product.code.trim().toLowerCase() === codeInput.value.trim().toLowerCase());
        if (savedProduct && !savedImage) previewImage(row, savedProduct);
        row.querySelector('[data-remove-new-row]')?.addEventListener('click', () => {
            if (!row.hasAttribute('data-new-row')) return;
            if (saving) { state.textContent = 'Vui lòng chờ tự lưu xong trước khi xóa dòng.'; return; }
            clearTimeout(saveTimer);
            row.remove();
            if (!document.querySelector('[data-new-row]')) appendNewRow();
        });
        function colorRow() {
            const status = row.querySelector('[name=status]').value;
            row.classList.toggle('is-arrived', status === 'da_ve');
            row.classList.toggle('is-ordered', status === 'da_od');
            row.querySelector('[name=status]').className = 'status-select st-' + status;
        }
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
        let saveTimer, saving = false, revision = 0;
        function scheduleSave() {
            revision++;
            clearTimeout(saveTimer);
            saveTimer = setTimeout(saveRow, 650);
        }
        const sizeCell = row.querySelector('[name=size_note]').closest('td');
        const sizeEditor = document.createElement('div'); sizeEditor.className = 'workshop-size-editor';
        const sizeList = document.createElement('div');
        const addSize = document.createElement('button'); addSize.type = 'button'; addSize.textContent = '+ Thêm size và số lượng';
        sizeEditor.append(sizeList, addSize); sizeCell.appendChild(sizeEditor);
        const managed = row.dataset.managedPlan === '1';
        let savedSizes = JSON.parse(row.dataset.sizeQuantities || '[]');
        function readSizes() {
            return Array.from(sizeList.children).filter(line => line.querySelector('select').value).map(line => ({
                product_id:Number(line.querySelector('select').value), quantity:Number(line.querySelector('input').value)
            }));
        }
        function sizeChanged() {
            const lines = readSizes();
            if (lines.length) {
                row.querySelector('[name=quantity]').value = lines.reduce((sum, line) => sum + line.quantity, 0);
            }
            row.classList.add('has-changes'); state.textContent = 'Chờ tự lưu…'; scheduleSave();
            row.querySelector('[data-row-total]').textContent = format(Number(row.querySelector('[name=quantity]').value) * Number(row.querySelector('[name=unit_price]').value));
        }
        function addSizeLine(data = {}) {
            const line = document.createElement('div'); line.className = 'workshop-size-line';
            const select = document.createElement('select'); select.setAttribute('aria-label', 'Size nhập kho');
            const empty = document.createElement('option'); empty.value = ''; empty.textContent = 'Chọn size'; select.appendChild(empty);
            const product = catalog.find(item => item.code.toLowerCase() === codeInput.value.trim().toLowerCase());
            (product?.sizes || []).forEach(size => { const option = document.createElement('option'); option.value = size.id; option.textContent = size.size; select.appendChild(option); });
            select.value = data.product_id || '';
            const quantity = document.createElement('input'); quantity.type = 'number'; quantity.min = '1'; quantity.step = '1'; quantity.value = data.quantity || 1; quantity.setAttribute('aria-label', 'Số lượng size nhập kho');
            const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = '×'; remove.setAttribute('aria-label', 'Xóa size nhập kho');
            remove.addEventListener('click', () => { line.remove(); sizeChanged(); });
            line.append(select, quantity, remove); sizeList.appendChild(line);
            if (managed || row.dataset.stocked === '1') { select.disabled = true; quantity.disabled = true; remove.disabled = true; }
        }
        savedSizes.forEach(addSizeLine);
        addSize.hidden = managed || row.dataset.stocked === '1';
        addSize.addEventListener('click', () => addSizeLine());
        sizeList.addEventListener('change', sizeChanged); sizeList.addEventListener('input', sizeChanged);
        codeInput.addEventListener('change', () => { if (!managed && row.dataset.stocked !== '1') { sizeList.replaceChildren(); savedSizes = []; } });
        if (managed) {
            const hint = document.createElement('small'); hint.textContent = 'Size và số lượng đồng bộ từ kế hoạch mua trên đơn khách.'; sizeEditor.appendChild(hint);
        }
        const stockButton = document.createElement('button'); stockButton.type = 'button'; stockButton.className = 'inline-save';
        row.querySelector('[data-stock-cell]').appendChild(stockButton);
        function updateStockButton() {
            stockButton.textContent = row.dataset.stocked === '1' ? 'Đã đẩy lên kho' : 'Đẩy lên kho';
            stockButton.disabled = !row.dataset.stockUrl || row.dataset.stocked === '1';
        }
        updateStockButton();
        stockButton.addEventListener('click', async () => {
            if (saving || row.classList.contains('has-changes')) { state.textContent = 'Vui lòng chờ tự lưu xong rồi đẩy lên kho.'; return; }
            stockButton.disabled = true;
            try {
                const body = new FormData(); body.append('_token', form.querySelector('[name=_token]').value);
                const response = await fetch(row.dataset.stockUrl, {method:'POST',body,credentials:'same-origin',headers:{Accept:'application/json'}});
                const data = await response.json();
                if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : data.message);
                row.dataset.stocked = '1'; row.querySelector('[name=status]').value = 'da_ve'; colorRow(); state.textContent = data.message;
                addSize.hidden = true; sizeList.querySelectorAll('input,select,button').forEach(input => input.disabled = true);
            } catch (error) { state.textContent = error.message; state.style.color = '#b42318'; }
            finally { updateStockButton(); }
        });
        function changed(event) {
            if (!event.target.matches('input,textarea,select')) return;
            row.classList.add('has-changes'); state.textContent = 'Chờ tự lưu…'; scheduleSave(); state.style.color = '';
            row.querySelector('[data-row-total]').textContent = format(Number(row.querySelector('[name=quantity]').value) * Number(row.querySelector('[name=unit_price]').value));
            if (event.target.name === 'product_code') {
                const code = event.target.value.trim().toLowerCase();
                const product = catalog.find(product => product.code.toLowerCase() === code);
                previewImage(row, customImage ? {image:customImage, code:codeInput.value, name:product?.name || (codeInput.value === @json(\App\Models\WorkshopOrder::NO_PRODUCT_CODE) ? 'Chưa tạo mã' : 'Chọn mã để hiện tên và ảnh')} : product);
            }
        }
        row.addEventListener('input', changed); row.addEventListener('change', changed);
        form.addEventListener('submit', event => { event.preventDefault(); clearTimeout(saveTimer); saveRow(); });
        async function saveRow() {
            if (saving || !row.isConnected || !row.classList.contains('has-changes')) return;
            if (!form.checkValidity()) { state.textContent = 'Chưa lưu: vui lòng kiểm tra các ô bắt buộc.'; return; }
            saving = true;
            const sentRevision = revision, sentImage = pendingImage;
            const body = new FormData(form);
            const sizes = readSizes();
            if (!managed) body.set('size_quantities', JSON.stringify(sizes));
            if (pendingImage) body.append('image', pendingImage);
            state.textContent = 'Đang lưu…';
            try {
                const response = await fetch(form.action, {method:'POST',body,credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
                const data = await response.json();
                if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Chưa lưu được.'));
                const isNew = row.hasAttribute('data-new-row');
                if (isNew) {
                    row.removeAttribute('data-new-row');
                    form.action = data.update_url;
                    row.dataset.stockUrl = data.stock_url; updateStockButton();
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
                if (revision === sentRevision) row.querySelector('[name=quantity]').value = data.quantity;
                if (revision === sentRevision) row.querySelector('[name=size_note]').value = data.size_note || '';
                if (revision === sentRevision) row.querySelector('[data-row-total]').textContent = format(data.total);
                row.querySelector('[name=status]').className = 'status-select st-' + row.querySelector('[name=status]').value;
                const selectedCode = row.querySelector('[name=product_code]').value;
                const selectedProduct = catalog.find(product => product.code.toLowerCase() === selectedCode.trim().toLowerCase());
                if (revision === sentRevision) previewImage(row, {name:data.product_name,image:data.image || selectedProduct?.image,code:selectedCode});
                if (pendingImage === sentImage) {
                    if (pendingImage) customImage = data.image || null;
                    pendingImage = null; upload.value = '';
                    if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
                }
                row.dataset.orderId = data.id;
                row.dataset.orderUrl = data.order_url || '';
                row.dataset.warehouseCode = data.product_code || '';
                row.dataset.warehouseUrl = data.product_url || '';
                refreshCodeLink(); refreshNameLink();
                applyDuplicateWarnings(data.duplicate_rows || {});
                if (revision === sentRevision) {
                    row.classList.remove('has-changes');
                    state.textContent = row.classList.contains('is-duplicate') ? 'Đã lưu · ⚠ Cảnh báo trùng đặt hàng' : 'Đã lưu';
                    state.style.color = row.classList.contains('is-duplicate') ? '#a51220' : '';
                }
            } catch (error) { state.textContent = 'Chưa lưu: ' + error.message; state.style.color = '#b42318'; }
            finally {
                saving = false;
                if (revision !== sentRevision) { clearTimeout(saveTimer); saveTimer = setTimeout(saveRow, 650); }
                if (managed || row.dataset.stocked === '1') sizeList.querySelectorAll('input,select,button').forEach(input => input.disabled = true);
            }
        }
    }
    let newRowIndex = 0;
    function appendNewRow() {
        const template = document.getElementById('workshop-new-row');
        const fragment = document.createElement('template');
        fragment.innerHTML = template.innerHTML.replaceAll('__NEW__', String(++newRowIndex));
        const row = fragment.content.querySelector('tr');
        document.querySelector('[data-workshop-rows]').prepend(row);
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
