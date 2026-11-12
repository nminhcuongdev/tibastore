const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

(async () => {
    let data = {note:'first', savedAt:1}, valid = true, method = null, calls = 0, draftSaves = 0;
    let resolveRequest;
    const status = {style:{}, setAttribute(){}};
    const form = {action:'/orders', appendChild(node){ if (node.name === '_method') method = node; },
        checkValidity:() => valid, querySelectorAll:() => [], querySelector:selector => selector === '[name=_method]' ? method : {textContent:''}, addEventListener(){}};
    const context = vm.createContext({
        document:{createElement:() => ({...status})}, orderForm:form, currentOrderId:null, orderDraftDiscarded:false,
        readFormDraft:() => ({...data}), restoredFromDraft:false, hasOldInput:false,
        refreshAvailability(){}, renumberRows(){}, itemsContainer:{querySelectorAll:selector => selector === '.size-row'
            ? [{querySelector:() => ({value:'1'})}] : [{dispatchEvent(){}}]},
        saveDraftNow(){draftSaves++;}, clearDraft(){}, DRAFT_KEY:'create', EDIT_DRAFT_PREFIX:'edit_',
        history:{replaceState(){}}, window:{addEventListener(){}, location:{assign(){}}},
        FormData:class {}, Event:class {}, AbortController, setTimeout:() => 1, clearTimeout(){}, setInterval(){},
        fetch:async () => { calls++; return await new Promise(resolve => {resolveRequest = resolve;}); },
    });
    vm.runInContext(fs.readFileSync('resources/views/orders/autosave.blade.php','utf8').replace("@include('partials.save-feedback')", fs.readFileSync('resources/views/partials/save-feedback.blade.php','utf8')), context);
    const run = () => vm.runInContext('saveOrderAutomatically()', context);
    const response = {ok:true, json:async () => ({id:42, update_url:'/orders/42', edit_url:'/orders/42/edit', show_url:'/orders/42'})};
    const first = run();
    data.note = 'edited during save';
    await run();
    assert.equal(calls, 1, 'Only one request may be in flight');
    resolveRequest(response); await first;
    assert.equal(form.action, '/orders/42'); assert.equal(method.value, 'PUT');
    assert.equal(context.currentOrderId, 42); assert.equal(context.DRAFT_KEY, 'edit_42');
    const second = run(); resolveRequest(response); await second;
    await run(); assert.equal(calls, 2, 'Unchanged content must not save repeatedly');
    valid = false; data.note = 'incomplete'; await run();
    assert.equal(calls, 2, 'Incomplete data stays as a local draft');
    assert.ok(draftSaves > 0);
    valid = true; data.note = 'invalid server data';
    const failed = run(); resolveRequest({ok:false, json:async () => ({errors:{items:['Cannot save']}})}); await failed;
    assert.equal(context.currentOrderId, 42, 'Failed saves retain the existing order identity');
    data.note = 'discard while saving';
    const discarded = run(); context.orderDraftDiscarded = true;
    const savedDraftCount = draftSaves;
    resolveRequest(response); await discarded;
    assert.equal(draftSaves, savedDraftCount, 'Discarded drafts must not be recreated after a pending response');
    const requestsBeforeDiscard = calls; await run();
    assert.equal(calls, requestsBeforeDiscard, 'Discard stops further autosaves');
    const formSource = fs.readFileSync('resources/views/orders/form.blade.php', 'utf8');
    const freshStart = formSource.indexOf('const createPageUrl =');
    const freshEnd = formSource.indexOf('if (! hasOldInput)', freshStart);
    let draftClears = 0;
    const location = {href:'https://example.test/orders/create?fresh=1'};
    const freshContext = vm.createContext({URL, window:{location}, isCreate:true,
        clearDraft(){draftClears++;}, history:{replaceState(a,b,url){location.href = url;}}});
    const freshCode = formSource.slice(freshStart, freshEnd);
    vm.runInContext('{'+freshCode+'}', freshContext);
    assert.equal(draftClears, 1, 'Create new must clear the previous create draft');
    assert.ok(!location.href.includes('fresh='), 'Reloading the new form must still allow restoring its new draft');
    vm.runInContext('{'+freshCode+'}', freshContext);
    assert.equal(draftClears, 1, 'A normal reload must not discard the new draft');
    console.log('Order autosave: serial requests, pending edits, deduplication and draft checks passed');
})().catch(error => { console.error(error); process.exitCode = 1; });
