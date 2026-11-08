        const autosaveState = document.createElement('div');
        autosaveState.setAttribute('role', 'status'); autosaveState.setAttribute('aria-live', 'polite');
        autosaveState.style.cssText = 'grid-column:1/-1;position:sticky;bottom:12px;z-index:100;padding:10px 16px;border:1px solid #cbd9ec;border-radius:8px;background:#fff;color:#203b60;box-shadow:0 2px 12px #203b601a';
        orderForm.appendChild(autosaveState);
        function autosaveSnapshot() {
            const snapshot = readFormDraft(); delete snapshot.savedAt;
            return JSON.stringify(snapshot);
        }
        const autosaveInitialSnapshot = autosaveSnapshot();
        let autosaveLastSaved = currentOrderId && !restoredFromDraft && !hasOldInput ? autosaveInitialSnapshot : '';
        let autosaveLastAttempt = '', autosaveBusy = false, autosaveFinish = false, autosaveTimer;
        autosaveState.textContent = currentOrderId ? 'Tự động lưu đã bật.' : 'Tự động lưu đã bật. Chưa đủ thông tin sẽ lưu nháp trên máy này.';
        function scheduleAutosave() {
            if (orderDraftDiscarded) return;
            clearTimeout(autosaveTimer);
            autosaveTimer = setTimeout(() => saveOrderAutomatically(), 1200);
        }
        async function saveOrderAutomatically(force = false) {
            if (autosaveBusy || orderDraftDiscarded) return;
            itemsContainer.querySelectorAll('.order-item').forEach(block => block.applyPurchaseBeforeSave?.());
            renumberRows();
            const snapshot = autosaveSnapshot();
            if (!force && (snapshot === autosaveLastSaved || snapshot === autosaveLastAttempt)) return;
            const sizeRows = Array.from(itemsContainer.querySelectorAll('.size-row'));
            if (!orderForm.checkValidity() || !sizeRows.length || sizeRows.some(row => !row.querySelector('[data-product-id]').value)) {
                autosaveLastAttempt = snapshot;
                saveDraftNow(); autosaveFinish = false;
                const invalid = Array.from(orderForm.querySelectorAll('input,select,textarea'))
                    .find(input => !input.disabled && !input.checkValidity());
                const label = invalid?.labels?.[0]?.textContent.trim()
                    || invalid?.getAttribute('aria-label') || invalid?.name || 'sản phẩm và size';
                autosaveState.textContent = 'Chưa lưu được đơn. Kiểm tra ' + label + (invalid?.validationMessage ? ' — ' + invalid.validationMessage : '.');
                if (force && invalid) invalid.reportValidity();
                return;
            }
            autosaveBusy = true; autosaveLastAttempt = snapshot; saveDraftNow();
            autosaveState.textContent = 'Đang tự động lưu…';
            try {
                const response = await fetch(orderForm.action, {method:'POST', body:new FormData(orderForm), credentials:'same-origin',
                    headers:{Accept:'application/json', 'X-Requested-With':'XMLHttpRequest'}});
                const data = await response.json();
                if (orderDraftDiscarded) return;
                if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Chưa lưu được.'));
                const firstSave = !currentOrderId;
                currentOrderId = data.id; orderForm.action = data.update_url;
                if (!orderForm.querySelector('[name=_method]')) {
                    const method = document.createElement('input'); method.type = 'hidden'; method.name = '_method'; method.value = 'PUT'; orderForm.appendChild(method);
                }
                if (firstSave) {
                    clearDraft(); DRAFT_KEY = EDIT_DRAFT_PREFIX + data.id;
                    history.replaceState(null, '', data.edit_url);
                    const submit = orderForm.querySelector('button[type=submit]'); if (submit) submit.textContent = 'Lưu và xem đơn';
                }
                autosaveLastSaved = snapshot;
                const unchanged = autosaveSnapshot() === snapshot;
                if (unchanged) {
                    clearDraft();
                    itemsContainer.querySelectorAll('.order-item').forEach(block => block.dispatchEvent(new Event('order-autosaved')));
                    refreshAvailability('saved');
                } else saveDraftNow();
                autosaveState.textContent = data.warning ? 'Đã lưu đơn #' + data.id + ' · ' + data.warning : 'Đã tự lưu đơn #' + data.id;
                if (autosaveFinish && unchanged) { window.location.assign(data.show_url); return; }
            } catch (error) {
                if (orderDraftDiscarded) return;
                saveDraftNow(); autosaveFinish = false;
                autosaveState.textContent = 'Chưa lưu vào hệ thống: ' + (error.message || 'Kiểm tra kết nối rồi thử lại.') + ' Nháp vẫn được giữ trên máy này.';
            } finally {
                autosaveBusy = false;
                if (!orderDraftDiscarded && autosaveSnapshot() !== snapshot) scheduleAutosave();
            }
        }
        orderForm.addEventListener('input', scheduleAutosave);
        orderForm.addEventListener('change', scheduleAutosave);
        orderForm.addEventListener('order-draft-discarded', () => { clearTimeout(autosaveTimer); autosaveFinish = false; });
        window.addEventListener('online', () => { autosaveLastAttempt = ''; scheduleAutosave(); });
        // Also cover choosing products, applying OD, removing rows and undo/redo.
        setInterval(scheduleAutosaveIfChanged, 1800);
        function scheduleAutosaveIfChanged() {
            if (orderDraftDiscarded) return;
            const snapshot = autosaveSnapshot();
            if (snapshot !== autosaveLastSaved && snapshot !== autosaveLastAttempt && !autosaveBusy) scheduleAutosave();
        }
        orderForm.addEventListener('submit', event => {
            if (event.defaultPrevented) return;
            event.preventDefault(); autosaveFinish = true; clearTimeout(autosaveTimer);
            saveOrderAutomatically(true);
        });
        window.addEventListener('beforeunload', event => {
            if (orderDraftDiscarded) return;
            const snapshot = autosaveSnapshot();
            if (snapshot !== autosaveLastSaved && (currentOrderId || restoredFromDraft || hasOldInput || snapshot !== autosaveInitialSnapshot)) {
                saveDraftNow(); event.preventDefault(); event.returnValue = '';
            }
        });
