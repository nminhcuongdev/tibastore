const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
(async () => {
    const ctx = vm.createContext({});
    vm.runInContext(fs.readFileSync('resources/views/workshop_orders/receipt-queue-save.blade.php', 'utf8'), ctx);
    let dirty = true, saved = 0, received = 0;
    await ctx.saveReceiptQueueRow(async () => { saved++; dirty = false; }, () => dirty, async () => { received++; });
    assert.equal(saved, 1); assert.equal(received, 1);
    dirty = true; saved = 0; received = 0;
    await ctx.saveReceiptQueueRow(async () => { saved++; if (saved === 2) dirty = false; }, () => dirty, async () => { received++; });
    assert.equal(saved, 2); assert.equal(received, 1);
    dirty = true; received = 0;
    assert.equal(await ctx.saveReceiptQueueRow(async () => {}, () => dirty, async () => { received++; }), false);
    assert.equal(received, 0, 'Failed validation or saving must never receive stock or move to the next row');
    await assert.rejects(ctx.saveReceiptQueueRow(async () => {}, () => false, async () => { throw new Error('stock failed'); }), /stock failed/);
    console.log('Receipt queue save tests passed');
})().catch(error => { console.error(error); process.exit(1); });
