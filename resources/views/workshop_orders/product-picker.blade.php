<div id="workshop-product-picker" class="product-picker no-print" hidden>
<div class="picker-head"><strong>Chọn sản phẩm</strong><button type="button" data-picker-close aria-label="Đóng gợi ý">×</button></div>
<button type="button" class="picker-option" data-picker-no-code>Chưa tạo mã — đặt hàng bằng ảnh</button>
<label class="picker-category">Danh mục<select id="picker-category" aria-label="Lọc theo danh mục"><option value="">Tất cả danh mục</option></select></label>
<input id="picker-search" type="search" placeholder="Tìm mã, tên hoặc danh mục…" aria-label="Tìm mã, tên hoặc danh mục">
<div id="picker-results" class="picker-results" role="listbox" aria-label="Sản phẩm gợi ý"></div>
<p id="picker-count" class="picker-count" role="status"></p>
</div>
<script>
(function () {
const catalog = @json($productCodes);
document.addEventListener('workshop-product-created', event => catalog.push(event.detail));
const body = document.querySelector('[data-workshop-rows]');
    const picker = document.getElementById('workshop-product-picker');
    const pickerSearch = document.getElementById('picker-search');
    const pickerCategory = document.getElementById('picker-category');
    const pickerResults = document.getElementById('picker-results');
    let activeCodeInput = null;
    let activeOption = -1;
    const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase().trim();
    const categories = [...new Set(catalog.map(product => product.category))].sort((a, b) => a.localeCompare(b, 'vi'));
    categories.forEach(category => { const option = document.createElement('option'); option.value = category; option.textContent = category; pickerCategory.appendChild(option); });
    function closePicker() {
        if (activeCodeInput) activeCodeInput.setAttribute('aria-expanded', 'false');
        picker.hidden = true; activeCodeInput = null;
    }
    function positionPicker() {
        if (!activeCodeInput || picker.hidden) return;
        const rect = activeCodeInput.getBoundingClientRect();
        const gap = 8;
        const width = Math.min(370, window.innerWidth - 2 * gap);
        picker.style.width = width + 'px';
        picker.style.left = Math.max(gap, Math.min(rect.left, window.innerWidth - width - gap)) + 'px';
        const spaceBelow = window.innerHeight - rect.bottom - gap;
        const above = spaceBelow < 280 && rect.top > spaceBelow;
        const available = Math.max(160, (above ? rect.top : window.innerHeight - rect.bottom) - 2 * gap);
        picker.style.maxHeight = Math.min(480, available) + 'px';
        const height = Math.min(picker.scrollHeight, available, 480);
        picker.style.top = Math.max(gap, above ? rect.top - height - gap : rect.bottom + gap) + 'px';
    }
    function matchingProducts(query, category) {
        const term = normalize(query);
        return catalog.filter(product => (!category || product.category === category) &&
            normalize(product.code + ' ' + product.name + ' ' + product.category).includes(term));
    }
    function choose(product) {
        if (!activeCodeInput) return;
        const input = activeCodeInput;
        input.value = product.code;
        input.dispatchEvent(new Event('input', {bubbles:true}));
        closePicker(); input.focus({preventScroll:true});
    }
    function renderProducts() {
        pickerResults.replaceChildren(); activeOption = -1;
        const matches = matchingProducts(pickerSearch.value, pickerCategory.value);
        matches.forEach(product => {
            const button = document.createElement('button'); button.type = 'button'; button.className = 'picker-option'; button.setAttribute('role', 'option');
            const thumbnail = document.createElement('span'); thumbnail.className = 'picker-thumbnail';
            if (product.image) {
                const image = document.createElement('img'); image.src = product.image; image.alt = product.name; image.loading = 'lazy';
                image.onerror = () => { thumbnail.replaceChildren(); thumbnail.textContent = 'Chưa có ảnh'; };
                thumbnail.appendChild(image);
            } else { thumbnail.textContent = 'Chưa có ảnh'; }
            const description = document.createElement('span'); description.className = 'picker-description';
            const code = document.createElement('strong'); code.textContent = product.code;
            const name = document.createElement('span'); name.textContent = product.name;
            const category = document.createElement('small'); category.textContent = product.category;
            description.append(code, name, category); button.append(thumbnail, description);
            button.addEventListener('click', () => choose(product)); pickerResults.appendChild(button);
        });
        if (!matches.length) { const empty = document.createElement('p'); empty.className = 'picker-empty'; empty.textContent = 'Không có sản phẩm phù hợp. Thử mã, tên hoặc danh mục khác.'; pickerResults.appendChild(empty); }
        document.getElementById('picker-count').textContent = matches.length + ' sản phẩm';
        positionPicker();
    }
    function openPicker(input) {
        if (activeCodeInput && activeCodeInput !== input) activeCodeInput.setAttribute('aria-expanded', 'false');
        activeCodeInput = input; picker.hidden = false;
        input.setAttribute('aria-expanded', 'true'); input.setAttribute('aria-controls', 'workshop-product-picker');
        pickerSearch.value = input.value; renderProducts();
    }
    body.addEventListener('click', event => { if (event.target.name === 'product_code') openPicker(event.target); });
    body.addEventListener('input', event => { if (event.target.name === 'product_code') openPicker(event.target); });
    pickerSearch.addEventListener('input', renderProducts);
    pickerCategory.addEventListener('change', renderProducts);
    picker.querySelector('[data-picker-close]').addEventListener('click', closePicker);
    picker.querySelector('[data-picker-no-code]').addEventListener('click', () => choose({code: @json(\App\Models\WorkshopOrder::NO_PRODUCT_CODE)}));
    document.addEventListener('pointerdown', event => { if (!picker.hidden && !picker.contains(event.target) && event.target !== activeCodeInput) closePicker(); });
    document.addEventListener('keydown', event => {
        if (picker.hidden) {
            if (event.key === 'ArrowDown' && event.target.name === 'product_code') { event.preventDefault(); openPicker(event.target); }
            return;
        }
        if (event.key === 'Escape') { const input = activeCodeInput; closePicker(); if (input) input.focus({preventScroll:true}); return; }
        if (event.target === pickerCategory) return;
        const options = Array.from(pickerResults.querySelectorAll('.picker-option'));
        if ((event.key === 'ArrowDown' || event.key === 'ArrowUp') && options.length) {
            event.preventDefault(); activeOption = Math.max(0, Math.min(options.length - 1, activeOption + (event.key === 'ArrowDown' ? 1 : -1)));
            options.forEach((option, index) => { option.classList.toggle('is-active', index === activeOption); option.setAttribute('aria-selected', String(index === activeOption)); });
            options[activeOption].scrollIntoView({block:'nearest'});
        } else if (event.key === 'Enter' && (event.target === activeCodeInput || event.target === pickerSearch)) {
            event.preventDefault(); if (options.length) options[Math.max(0, activeOption)].click();
        } else if (event.key === 'Tab' && event.target === activeCodeInput) closePicker();
    });
    window.addEventListener('resize', positionPicker);
    window.addEventListener('scroll', event => { if (!picker.contains(event.target)) positionPicker(); }, true);

})();
</script>
