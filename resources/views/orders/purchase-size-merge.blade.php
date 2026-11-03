        function purchaseSizeUpdates(existing, planned, applied) {
            const present = new Set(existing.map(line => Number(line.product_id)));
            const previous = new Map(applied.map(line => [Number(line.product_id), Number(line.quantity)]));
            const totals = new Map();
            planned.forEach(line => totals.set(Number(line.product_id), (totals.get(Number(line.product_id)) || 0) + Number(line.quantity)));
            const additions = [];
            const removals = [];
            previous.forEach((quantity, productId) => {
                if (!totals.has(productId)) totals.set(productId, 0);
            });
            totals.forEach((quantity, productId) => {
                const already = present.has(productId) ? (previous.get(productId) || 0) : 0;
                if (quantity > already) additions.push({product_id:productId, quantity:quantity - already});
                if (quantity < already) removals.push({product_id:productId, quantity:already - quantity});
            });
            return {additions, removals, applied:Array.from(totals, ([product_id, quantity]) => ({product_id, quantity})).filter(line => line.quantity > 0)};
        }
