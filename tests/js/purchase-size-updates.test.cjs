const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const context = vm.createContext({});
vm.runInContext(fs.readFileSync('resources/views/orders/purchase-size-merge.blade.php', 'utf8'), context);
const update = (existing, planned, applied = []) => JSON.parse(JSON.stringify(context.purchaseSizeUpdates(existing, planned, applied)));
const line = (product_id, quantity) => ({product_id, quantity});

// L already in the order stays intact; only the missing M is added.
const original = [line(10, 1)];
const first = update(original, [line(20, 1)]);
assert.deepEqual(original, [line(10, 1)]);
assert.deepEqual(first.additions, [line(20, 1)]);
const existing = [line(10, 1), line(20, 1)];
assert.deepEqual(update(existing, [line(20, 1)], first.applied).additions, []);

// Additional purchases of an existing size add only their own quantity.
const sameSize = update([line(10, 2)], [line(10, 3)]);
assert.deepEqual(sameSize.additions, [line(10, 3)]);
assert.deepEqual(update([line(10, 5)], [line(10, 3)], sameSize.applied).additions, []);
assert.deepEqual(update([line(10, 5)], [line(10, 4)], sameSize.applied).additions, [line(10, 1)]);

// Reductions remove only the previously applied purchase, preserving stock quantities.
const lower = update([line(10, 5)], [line(10, 1)], sameSize.applied);
assert.deepEqual(lower.additions, []);
assert.deepEqual(lower.removals, [line(10, 2)]);
assert.deepEqual(update([line(10, 3)], [line(10, 1)], lower.applied).removals, []);
assert.deepEqual(update([line(10, 3)], [line(10, 3)], lower.applied).additions, [line(10, 2)]);
// Changing size reverses the old purchase and adds the new size once.
const changed = update([line(10, 5)], [line(20, 3)], sameSize.applied);
assert.deepEqual(changed.removals, [line(10, 3)]);
assert.deepEqual(changed.additions, [line(20, 3)]);
const repeated = update([line(10, 2), line(20, 3)], [line(20, 3)], changed.applied);
assert.deepEqual(repeated.additions, []);
assert.deepEqual(repeated.removals, []);
assert.deepEqual(update([], [line(20, 1)], first.applied).additions, [line(20, 1)]);
assert.deepEqual(update([], [line(20, 1), line(20, 2)]).additions, [line(20, 3)]);
console.log('Purchase size additions: all checks passed');
