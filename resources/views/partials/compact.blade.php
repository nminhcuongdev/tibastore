{{-- Nén giao diện cho đặc thông tin. Include SAU thẻ <style> của trang để đè được. --}}
<style>
    /* ---- Cỡ chữ nhỏ lại toàn hệ thống ---- */
    body { font-size: 13px; }
    h1 { font-size: 20px !important; line-height: 1.3; margin: 0 0 10px !important; }
    h2 { font-size: 16px !important; margin: 16px 0 8px !important; }
    h3 { font-size: 14px !important; }
    label { font-size: 12px !important; }
    .muted, .sub, .hint { font-size: 12px !important; }
    .sub { margin: 0 0 12px !important; }

    input, select, textarea, button, .button {
        font-size: 13px !important;
    }
    input, select, textarea {
        min-height: 32px !important;
        padding: 6px 9px !important;
    }
    textarea { min-height: 60px !important; }
    .button {
        min-height: 32px !important;
        padding: 6px 12px !important;
    }

    /* ---- Bảng gọn lại: đây là chỗ ăn nhiều diện tích nhất ---- */
    th, td { padding: 6px 9px !important; }
    thead th { font-size: 11px !important; }
    tbody td { font-size: 13px !important; }

    .content { padding: 16px clamp(12px, 2vw, 26px) 32px !important; }

    /* ---- Một thanh cuộn duy nhất ----
       Bỏ mọi khung cuộn lồng bên trong để chỉ còn trang tự cuộn.
       Nhờ vậy tiêu đề bảng dính position:sticky bám theo cửa sổ thay vì bám
       vào khung con, và không còn cảnh cuộn trong khung rồi lại cuộn trang. */
    .table-shell {
        max-height: none !important;
        overflow: visible !important;
    }
    /* Bảng tự co theo nội dung thay vì bị ép rộng cố định. */
    .table-shell table { min-width: 0 !important; }

    /* Tiêu đề bảng bám cửa sổ khi cuộn trang. */
    thead th {
        position: sticky;
        top: 0;
        z-index: 3;
    }

    /* Sidebar không tự cuộn riêng nữa, tránh thanh cuộn thứ hai. */
    .app-sidebar { overflow-y: visible !important; }
</style>
