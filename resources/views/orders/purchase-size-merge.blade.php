        function purchaseSizeUpdates(existing, planned, applied) {
            const present = new Set(existing.map(line => Number(line.product_id)));
            const previous = new Map(applied.map(line => [Number(line.product_id), Number(line.quantity)]));
            const totals = new Map();
            planned.forEach(line => totals.set(Number(line.product_id), (totals.get(Number(line.product_id)) || 0) + Number(line.quantity)));
            const additions = [];
            totals.forEach((quantity, productId) => {
                const already = present.has(productId) ? (previous.get(productId) || 0) : 0;
                if (quantity > already) additions.push({product_id:productId, quantity:quantity - already});
                previous.set(productId, Math.max(already, quantity));
            });
            return {additions, applied:Array.from(previous, ([product_id, quantity]) => ({product_id, quantity}))};
        }
