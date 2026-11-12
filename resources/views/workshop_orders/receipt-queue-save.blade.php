async function saveReceiptQueueRow(save, isDirty, receive) {
    await save();
    // Một lần tự lưu đang chạy có thể chưa chứa chỉnh sửa cuối cùng.
    if (isDirty()) await save();
    if (isDirty()) return false;
    return receive();
}
