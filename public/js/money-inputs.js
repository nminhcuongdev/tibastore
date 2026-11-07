(() => {
    const selector = [
        'input[name="shipping_fee"]', 'input[name="payment_1"]', 'input[name="payment_2"]',
        'input[name="unit_price"]', 'input[name="compensation_amount"]',
        'input[data-block-rental]', 'input[data-field="unit_price"]',
        'input[name="rental_price_text"]',
        '#discount_amount', '#discount-value',
    ].join(',');
    const nativeValue = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
    const enhanced = new Set();
    const percentMode = new WeakMap();

    function isPercent(input) {
        const type = input.id === 'discount_amount' ? document.getElementById('discount_type')
            : input.id === 'discount-value' ? document.getElementById('discount-type') : null;
        return type?.value === 'percent';
    }

    function raw(input) {
        const value = nativeValue.get.call(input);
        if (input.name === 'rental_price_text' && /[^\d.,\s]/.test(value)) return value;
        return percentMode.get(input) ? value.replace(',', '.') : value.replaceAll('.', '').replace(',', '.');
    }

    function render(input, value) {
        if (input.name === 'rental_price_text' && /^\d{1,3}(\.\d{3})+$/.test(String(value))) {
            value = String(value).replaceAll('.', '');
        }
        if (input.name === 'rental_price_text' && /[^\d.,\s]/.test(String(value))) {
            nativeValue.set.call(input, value);
            input.setCustomValidity('');
            return;
        }
        const parts = String(value ?? '').split('.');
        const display = isPercent(input) ? String(value ?? '')
            : parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.') + (parts.length > 1 ? ',' + parts[1] : '');
        nativeValue.set.call(input, display);
        percentMode.set(input, isPercent(input));
        const number = Number(value);
        let error = '';
        if (value !== '' && (!Number.isFinite(number) || !/^-?\d+(\.\d+)?$/.test(value))) error = 'Vui lòng nhập số tiền hợp lệ.';
        if (value !== '' && input.min !== '' && number < Number(input.min)) error = 'Giá trị tối thiểu là ' + input.min + '.';
        if (value !== '' && input.max !== '' && number > Number(input.max)) error = 'Giá trị tối đa là ' + input.max + '.';
        input.setCustomValidity(error);
    }

    function enhance(input) {
        if (enhanced.has(input) || (input.type !== 'number' && input.name !== 'rental_price_text')) return;
        const value = input.value;
        input.type = 'text';
        input.inputMode = 'decimal';
        Object.defineProperty(input, 'value', {
            configurable: true,
            get() { return raw(this); },
            set(value) { render(this, String(value ?? '')); },
        });
        enhanced.add(input);
        render(input, value);
    }

    function scan(root) {
        if (root.matches?.(selector)) enhance(root);
        root.querySelectorAll?.(selector).forEach(enhance);
    }

    // Run before page listeners read amounts for totals and autosave.
    document.addEventListener('input', event => {
        const input = event.target;
        if (!input.matches?.(selector)) return;
        enhance(input);
        if (input.name === 'rental_price_text' && /[^\d.,\s]/.test(nativeValue.get.call(input))) {
            input.setCustomValidity('');
            return;
        }
        const cursor = input.selectionStart;
        const before = nativeValue.get.call(input).slice(0, cursor).replace(/[^0-9]/g, '').length;
        render(input, raw(input));
        let position = 0, digits = 0;
        while (position < nativeValue.get.call(input).length && digits < before) {
            if (/\d/.test(nativeValue.get.call(input)[position])) digits++;
            position++;
        }
        input.setSelectionRange(position, position);
    }, true);

    document.addEventListener('change', event => {
        if (!['discount_type', 'discount-type'].includes(event.target.id)) return;
        const input = document.getElementById(event.target.id === 'discount_type' ? 'discount_amount' : 'discount-value');
        if (input && enhanced.has(input)) {
            const value = raw(input);
            render(input, value);
        }
    }, true);

    // FormData uses the DOM value, so submit plain numeric amounts.
    document.addEventListener('formdata', event => {
        for (const input of enhanced) {
            if (input.form === event.target && input.name && !input.disabled) event.formData.set(input.name, input.value);
        }
    }, true);
    document.addEventListener('reset', event => {
        setTimeout(() => {
            for (const input of enhanced) if (input.form === event.target) render(input, input.defaultValue);
        });
    }, true);
    const start = () => {
        scan(document);
        new MutationObserver(records => {
            for (const record of records) record.addedNodes.forEach(scan);
            for (const input of enhanced) if (!input.isConnected) enhanced.delete(input);
        }).observe(document.body, {childList: true, subtree: true});
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
