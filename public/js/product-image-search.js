const root = document.querySelector('[data-image-search]');
const input = document.getElementById('search-image');
const preview = root.querySelector('[data-preview]');
const search = root.querySelector('[data-search]');
const cancel = root.querySelector('[data-cancel]');
const status = root.querySelector('[data-status]');
const progress = root.querySelector('[data-progress]');
const progressBox = root.querySelector('[data-progress-box]');
const results = root.querySelector('[data-results]');
let selected;
let previewUrl;
let worker;
let runId = 0;
let busy = false;
let choiceId = 0;

function message(text, error = false) {
    status.textContent = text;
    status.classList.toggle('error', error);
}

function setBusy(value) {
    busy = value;
    input.disabled = value;
    search.disabled = value || !selected;
    cancel.hidden = !value;
    progressBox.hidden = !value;
}

async function choose(file) {
    if (busy || !file) return;
    const choice = ++choiceId;
    selected = null;
    search.disabled = true;
    preview.hidden = true;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 10 * 1024 * 1024) {
        message('Chọn ảnh JPG, PNG hoặc WebP không quá 10 MB.', true);
        return;
    }
    try {
        const bitmap = await createImageBitmap(file);
        if (choice !== choiceId) { bitmap.close(); return; }
        const scale = Math.min(1, 1024 / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(bitmap.width * scale));
        canvas.height = Math.max(1, Math.round(bitmap.height * scale));
        const context = canvas.getContext('2d');
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();
        const converted = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', .92));
        if (choice !== choiceId) return;
        if (!converted) throw new Error('Invalid image');
        selected = converted;
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(selected);
        preview.src = previewUrl;
        preview.hidden = false;
        search.disabled = false;
        results.replaceChildren();
        root.querySelector('[data-result-title]').hidden = true;
        root.querySelector('[data-result-note]').hidden = true;
        message('Ảnh đã sẵn sàng. Bấm “Tìm mã bằng ảnh” để bắt đầu.');
    } catch { if (choice === choiceId) message('Không đọc được ảnh. Hãy chọn ảnh khác.', true); }
}

input.addEventListener('change', () => choose(input.files[0]));
document.addEventListener('paste', event => {
    const item = Array.from(event.clipboardData?.items || []).find(item => item.type.startsWith('image/'));
    if (item && !busy) { event.preventDefault(); choose(item.getAsFile()); }
});

search.addEventListener('click', async () => {
    if (!selected || busy) return;
    const currentRun = ++runId;
    setBusy(true);
    results.replaceChildren();
    root.querySelector('[data-result-title]').hidden = true;
    root.querySelector('[data-result-note]').hidden = true;
    progress.removeAttribute('value');
    message('Đang đọc danh sách ảnh trong kho…');
    try {
        const response = await fetch(root.dataset.catalogUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (currentRun !== runId) return;
        if (!response.ok) throw new Error('Catalog failed');
        const catalog = await response.json();
        if (currentRun !== runId) return;
        if (!catalog.products?.length) {
            setBusy(false);
            message('Kho chưa có sản phẩm đang dùng có ảnh. Hãy thêm ảnh sản phẩm trước.');
            return;
        }
        if (!worker) worker = new Worker(new URL('./product-image-search-worker.js', import.meta.url), { type: 'module' });
        worker.onerror = () => {
            if (currentRun !== runId) return;
            worker.terminate(); worker = null;
            setBusy(false);
            message('Không chạy được tìm ảnh trên thiết bị này. Kiểm tra mạng và thử Chrome/Edge bản mới.', true);
        };
        worker.onmessage = event => {
            if (currentRun !== runId) return;
            const data = event.data;
            if (data.type === 'progress') {
                message(data.message);
                if (data.progress === null) progress.removeAttribute('value'); else progress.value = data.progress;
            } else if (data.type === 'error') {
                console.error('Image search:', data.detail);
                worker.terminate(); worker = null;
                setBusy(false); message(data.message, true);
            } else if (data.type === 'done') {
                setBusy(false);
                root.querySelector('[data-result-title]').hidden = !data.results.length;
                root.querySelector('[data-result-note]').hidden = !data.results.length;
                data.results.forEach((product, index) => {
                    const card = document.createElement('a');
                    card.className = 'result'; card.href = product.url;
                    const image = document.createElement('img');
                    image.src = product.image; image.alt = product.name; image.loading = 'lazy';
                    const info = document.createElement('div');
                    const code = document.createElement('strong'); code.textContent = product.code;
                    const name = document.createElement('span'); name.textContent = product.name;
                    const detail = document.createElement('small');
                    detail.textContent = `Gợi ý ${index + 1} · ${product.category || 'Chưa phân loại'} · Tồn ${product.quantity}`;
                    info.append(code, name, detail); card.append(image, info); results.appendChild(card);
                });
                message(data.results.length ? `Đã tìm thấy ${data.results.length} mẫu gần giống để đối chiếu.${data.skipped ? ` Có ${data.skipped} ảnh kho không đọc được.` : ''}` : 'Không đọc được ảnh kho để so khớp. Kiểm tra ảnh sản phẩm và thử lại.');
            }
        };
        worker.postMessage({ image: selected, products: catalog.products });
    } catch {
        if (currentRun !== runId) return;
        setBusy(false); message('Chưa đọc được kho ảnh. Kiểm tra kết nối hoặc đăng nhập lại rồi thử.', true);
    }
});

cancel.addEventListener('click', () => {
    runId++;
    worker?.terminate(); worker = null;
    setBusy(false); message('Đã dừng tìm. Bạn có thể chọn ảnh khác hoặc tìm lại.');
});
window.addEventListener('pagehide', () => { worker?.terminate(); if (previewUrl) URL.revokeObjectURL(previewUrl); });
