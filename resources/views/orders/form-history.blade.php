        // The editor keeps five local steps, including unsaved product blocks and plans.
        // Saved account history remains available on the orders list.
        function editorSnapshot() {
            const data = readFormDraft();
            delete data.savedAt; delete data.items;
            data.blocks = Array.from(itemsContainer.querySelectorAll('.order-item')).map(block => ({
                code: block.dataset.productCode || null,
                search: block.querySelector('[data-search]').value,
                category: block.querySelector('[data-category]').value,
                rental: block.querySelector('[data-block-rental]').value,
                days: block.querySelector('[data-block-days]').value,
                fulfillment_source: block.querySelector('[data-supply-choice]').value,
                purchase_plan: block.purchasePlan || null,
                sizes: Array.from(block.querySelectorAll('.size-row')).map(row => ({
                    product_id: row.querySelector('[data-product-id]').value,
                    quantity: row.querySelector('[data-quantity]').value,
                    size_pending: isPendingRow(row) ? 1 : 0,
                    note: row.querySelector('[data-note]').value,
                })),
            }));
            return JSON.stringify(data);
        }
        let editorUndo = [editorSnapshot()], editorRedo = [], editorHistoryTimer;
        function captureEditorHistory() {
            const next = editorSnapshot();
            if (next === editorUndo[editorUndo.length - 1]) return;
            editorUndo.push(next);
            if (editorUndo.length > 6) editorUndo.shift();
            editorRedo = [];
        }
        function queueEditorHistory() {
            clearTimeout(editorHistoryTimer);
            editorHistoryTimer = setTimeout(captureEditorHistory, 650);
        }
        function restoreEditorHistory(value) {
            const data = JSON.parse(value);
            restoreTopFields(data);
            itemsContainer.replaceChildren();
            data.blocks.forEach(saved => {
                const block = addItemBlock(saved);
                block.querySelector('[data-search]').value = saved.search;
                block.querySelector('[data-category]').value = saved.category;
                block.querySelector('[data-block-rental]').value = saved.rental;
                block.querySelector('[data-block-days]').value = saved.days;
                block.querySelectorAll('.size-row').forEach((row, i) => {
                    row.querySelector('[data-quantity]').value = saved.sizes[i].quantity;
                });
            });
            renumberRows(); refreshProductChoices(); updateComputedTotal();
            saveDraftNow();
        }
        orderForm.addEventListener('input', queueEditorHistory);
        orderForm.addEventListener('change', queueEditorHistory);
        orderForm.addEventListener('click', event => {
            if (event.target.closest('button[type="button"]')) queueEditorHistory();
        });
        orderForm.addEventListener('drop', queueEditorHistory);
        document.addEventListener('personal-history-request', event => {
            event.preventDefault();
            clearTimeout(editorHistoryTimer); captureEditorHistory();
            const state = document.querySelector('[data-personal-state]');
            if (event.detail.direction === 'redo' && editorRedo.length) {
                const next = editorRedo.pop(); editorUndo.push(next); restoreEditorHistory(next);
                state.textContent = 'Đã làm lại nội dung đang sửa. Lưu đơn để lưu thay đổi.';
            } else if (event.detail.direction === 'undo' && editorUndo.length > 1) {
                editorRedo.push(editorUndo.pop()); restoreEditorHistory(editorUndo[editorUndo.length - 1]);
                state.textContent = 'Đã hoàn tác nội dung đang sửa. Lưu đơn để lưu thay đổi.';
            } else {
                state.textContent = 'Không còn bước để khôi phục trên trang này (tối đa 5 bước).';
            }
        });
