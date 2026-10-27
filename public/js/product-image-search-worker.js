const MODEL = 'Xenova/clip-vit-base-patch32';
const CACHE_VERSION = 'clip-p32-q8-v1';
let runtime;
let database;

function report(message, progress = null) {
    self.postMessage({ type: 'progress', message, progress });
}

async function loadRuntime() {
    if (!runtime) {
        runtime = (async () => {
            report('Đang tải mô hình nhận diện ảnh cho lần sử dụng đầu tiên…');
            const { AutoProcessor, CLIPVisionModelWithProjection, RawImage, env } = await import(
                'https://cdn.jsdelivr.net/npm/@xenova/transformers@2.17.2/dist/transformers.min.js'
            );
            env.allowLocalModels = false;
            env.backends.onnx.wasm.numThreads = 1;
            const processor = await AutoProcessor.from_pretrained(MODEL);
            const model = await CLIPVisionModelWithProjection.from_pretrained(MODEL, {
                quantized: true,
                progress_callback: event => {
                    if (event.status === 'progress') report('Đang tải mô hình AI… ' + Math.round(event.progress || 0) + '%');
                },
            });
            return { processor, model, RawImage };
        })();
    }
    return runtime;
}

async function cacheDb() {
    if (database !== undefined) return database;
    try {
        database = await new Promise((resolve, reject) => {
            const request = indexedDB.open('tiba-image-search', 1);
            request.onupgradeneeded = () => request.result.createObjectStore('embeddings');
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    } catch { database = null; }
    return database;
}

async function cached(key, value) {
    const db = await cacheDb();
    if (!db) return null;
    return new Promise(resolve => {
        try {
            const tx = db.transaction('embeddings', value ? 'readwrite' : 'readonly');
            const store = tx.objectStore('embeddings');
            const request = value ? store.put(value, key) : store.get(key);
            request.onsuccess = () => resolve(request.result || null);
            request.onerror = () => resolve(null);
            tx.onabort = () => resolve(null);
        } catch { resolve(null); }
    });
}

async function embedding(source) {
    const { processor, model, RawImage } = await loadRuntime();
    const image = await RawImage.read(source);
    const inputs = await processor(image);
    let outputs;
    try {
        outputs = await model(inputs);
        const data = Float32Array.from(outputs.image_embeds.data);
        const length = Math.sqrt(data.reduce((sum, x) => sum + x * x, 0)) || 1;
        return data.map(value => value / length);
    } finally {
        Object.values(inputs).forEach(tensor => tensor.dispose?.());
        if (outputs) Object.values(outputs).forEach(tensor => tensor.dispose?.());
    }
}

self.onmessage = async event => {
    const { image, products } = event.data;
    let source;
    try {
        await loadRuntime();
        source = URL.createObjectURL(image);
        report('Đang nhận diện trang phục trong ảnh bạn chọn…');
        const query = await embedding(source);
        const total = products.reduce((sum, product) => sum + product.images.length, 0);
        let processed = 0;
        let skipped = 0;
        const ranked = [];
        for (const product of products) {
            let best = null;
            let bestImage;
            for (const image of product.images) {
                try {
                    const url = new URL(image.url, self.location.origin);
                    if (url.origin !== self.location.origin || !url.pathname.startsWith('/storage/')) throw new Error('Invalid image');
                    const key = CACHE_VERSION + '|' + url.href + '|' + image.version;
                    let vector = await cached(key);
                    if (!vector || vector.length !== query.length) {
                        url.searchParams.set('v', image.version);
                        vector = await embedding(url.href);
                        await cached(key, vector);
                    }
                    const score = query.reduce((sum, x, index) => sum + x * vector[index], 0);
                    if (best === null || score > best) { best = score; bestImage = image.url; }
                } catch { skipped++; }
                processed++;
                report(`Đang so khớp ảnh kho: ${processed}/${total}. Lần đầu có thể mất vài phút.`, Math.round(processed / total * 100));
            }
            if (best !== null) ranked.push({ ...product, score: best, image: bestImage });
        }
        ranked.sort((a, b) => b.score - a.score || a.code.localeCompare(b.code));
        self.postMessage({ type: 'done', results: ranked.slice(0, 8), skipped, total });
    } catch (error) {
        console.error('Image search failed:', error);
        self.postMessage({ type: 'error', detail: String(error?.stack || error), message: 'Chưa tải hoặc chạy được mô hình AI. Kiểm tra Internet, dùng Chrome/Edge bản mới và thử lại. Nếu thiết bị thiếu bộ nhớ, hãy đóng bớt tab.' });
    } finally {
        if (source) URL.revokeObjectURL(source);
    }
};
