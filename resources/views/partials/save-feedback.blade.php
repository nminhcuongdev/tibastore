function saveInputError(input) {
    if (!input) return 'Vui lòng chọn sản phẩm và size trước khi lưu.';
    const label = input.getAttribute('aria-label') || input.labels?.[0]?.textContent.trim() || input.name || 'bắt buộc';
    const validity = input.validity;
    if (validity?.valueMissing) return 'Ô “' + label + '” đang trống. Vui lòng nhập hoặc chọn giá trị.';
    if (validity?.rangeUnderflow) return 'Ô “' + label + '” phải từ ' + input.min + ' trở lên.';
    if (validity?.rangeOverflow) return 'Ô “' + label + '” không được lớn hơn ' + input.max + '.';
    if (validity?.badInput || validity?.stepMismatch) return 'Ô “' + label + '” có số không hợp lệ. Vui lòng kiểm tra lại.';
    return 'Kiểm tra ô “' + label + '”: ' + (input.validationMessage || 'Giá trị không hợp lệ.');
}
async function sendSaveRequest(url, options) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 25000);
    try {
        const response = await fetch(url, {...options, signal:controller.signal});
        if (response.redirected || response.status === 401 || response.status === 419) throw new Error('Phiên đăng nhập đã hết hạn. Mở trang đăng nhập ở tab khác, đăng nhập lại rồi quay lại thử lưu.');
        if (response.status === 403) throw new Error('Tài khoản không có quyền lưu thay đổi này. Hãy nhờ quản trị viên kiểm tra quyền.');
        if (response.status === 413) throw new Error('Ảnh hoặc dữ liệu gửi lên quá lớn. Chọn ảnh nhỏ hơn rồi thử lại.');
        if (response.status === 429) throw new Error('Hệ thống đang nhận quá nhiều yêu cầu. Chờ một lát rồi thử lưu lại.');
        if (response.status >= 500) throw new Error('Máy chủ gặp lỗi (' + response.status + '). Chưa xác nhận được thay đổi đã lưu; giữ nguyên trang và thử lại sau.');
        let data;
        try { data = await response.json(); }
        catch (_) { throw new Error('Máy chủ trả về phản hồi không hợp lệ. Chưa xác nhận được thay đổi đã lưu; giữ nguyên trang và thử lại.'); }
        if (!response.ok) {
            const error = new Error(data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Không lưu được (mã ' + response.status + '). Hãy thử lại.'));
            error.repairActions = data.repair_actions || [];
            throw error;
        }
        return data;
    } catch (error) {
        if (error.name === 'AbortError') throw new Error('Quá 25 giây chưa nhận được xác nhận lưu. Kiểm tra kết nối và thử lại; đừng đóng trang.');
        if (error instanceof TypeError) throw new Error('Không kết nối được máy chủ. Kiểm tra mạng rồi thử lưu lại; đừng đóng trang.');
        throw error;
    } finally { clearTimeout(timeout); }
}
