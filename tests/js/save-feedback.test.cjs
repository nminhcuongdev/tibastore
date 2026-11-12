const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

(async () => {
    let reply, failure, abort;
    const context = vm.createContext({AbortController, TypeError,
        setTimeout(fn) { abort = fn; return 1; }, clearTimeout() {},
        fetch: async () => { if (failure) throw failure; return reply; }});
    vm.runInContext(fs.readFileSync('resources/views/partials/save-feedback.blade.php', 'utf8'), context);
    const send = () => context.sendSaveRequest('/save', {});
    for (const [status, message] of [[419, 'đăng nhập'], [403, 'quyền'], [413, 'quá lớn'], [429, 'quá nhiều'], [500, 'Máy chủ gặp lỗi']]) {
        reply = {status, ok:false};
        await assert.rejects(send(), error => error.message.includes(message));
    }
    reply = {status:200, ok:true, redirected:true};
    await assert.rejects(send(), /đăng nhập/);
    reply = {status:200, ok:true, json:async () => { throw new SyntaxError('HTML'); }};
    await assert.rejects(send(), /phản hồi không hợp lệ/);
    reply = {status:422, ok:false, json:async () => ({errors:{size:['Size đã tồn tại.']},repair_actions:[{label:'Sửa size',url:'/orders/1/edit?repair_product=2'}]})};
    await assert.rejects(send(), error => error.message.includes('Size đã tồn tại') && error.repairActions[0].url === '/orders/1/edit?repair_product=2');
    failure = new TypeError('Failed to fetch');
    await assert.rejects(send(), /Kiểm tra mạng/);
    failure = Object.assign(new Error('Aborted'), {name:'AbortError'});
    await assert.rejects(send(), /25 giây/);
    failure = null;
    reply = {status:200, ok:true, json:async () => ({saved:true})};
    assert.equal((await send()).saved, true);
    assert.match(context.saveInputError({validity:{valueMissing:true},getAttribute:()=> 'Size mới'}), /Size mới.*đang trống/);
    assert.match(context.saveInputError({validity:{rangeUnderflow:true}, min:'0',getAttribute:()=> 'Tồn kho'}), /Tồn kho.*từ 0/);
    console.log('Save feedback tests passed');
})().catch(error => { console.error(error); process.exit(1); });
