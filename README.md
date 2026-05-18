# Tiba Boutique Store

Ứng dụng quản lí kho và đơn hàng cho cửa hàng thời trang, xây dựng bằng Laravel 9.

## Chức năng chính

- Đăng nhập bằng mã đăng nhập và mật khẩu.
- Quản lí kho hàng:
  - Mã sản phẩm và size là một cặp không được trùng.
  - Tên sản phẩm, hình ảnh, số lượng tồn, vải, size, giá nhập.
  - Tìm kiếm theo mã hoặc tên sản phẩm.
  - Sắp xếp theo mã, tên, số lượng.
  - Phân trang.
- Chi tiết sản phẩm:
  - Xem các đơn hàng liên quan tới sản phẩm.
  - Biểu đồ biến động tồn kho dự kiến theo thời gian.
- Lịch sử nhập kho:
  - Tự ghi nhận khi tạo sản phẩm có tồn ban đầu.
  - Tự ghi nhận khi tăng số lượng tồn trong kho.
- Quản lí đơn hàng:
  - Người chốt, ngày lấy, ngày diễn, ngày trả, tên đơn, mã hàng, số lượng, trạng thái.
  - Trạng thái gồm: `Lên đơn`, `Đã gửi`, `Thành công`.
  - Kho chỉ bị trừ khi đơn chuyển sang `Đã gửi`.
  - Kho được hoàn lại khi đơn chuyển sang `Thành công`.
  - Popup nhắc đơn đến ngày lấy/ngày trả, có xác nhận từng đơn và tùy chọn không nhắc lại.

## Yêu cầu môi trường

- PHP `^8.0.2`
- Composer
- MySQL hoặc MariaDB
- XAMPP khuyến nghị nếu chạy trên Windows
- Git

## Setup dự án

Clone source code:

```bash
git clone https://github.com/nminhcuongdev/tibastore.git
cd tibastore
```

Cài dependency PHP:

```bash
composer install
```

Tạo file môi trường:

```bash
cp .env.example .env
```

Nếu dùng PowerShell trên Windows:

```powershell
Copy-Item .env.example .env
```

Tạo app key:

```bash
php artisan key:generate
```

## Cấu hình database

Tạo database MySQL, ví dụ:

```sql
CREATE DATABASE tibashop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Cập nhật `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tibashop
DB_USERNAME=root
DB_PASSWORD=
```

Nếu chạy bằng XAMPP trên Windows và `php` chưa có trong PATH, dùng PHP của XAMPP:

```powershell
D:\xampp\php\php.exe artisan key:generate
D:\xampp\php\php.exe artisan migrate
D:\xampp\php\php.exe artisan db:seed
D:\xampp\php\php.exe artisan storage:link
```

Nếu `php` đã có trong PATH:

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

Seeder sẽ tạo tài khoản đăng nhập mẫu:

```text
Mã đăng nhập: admin
Mật khẩu: password
```

## Chạy ứng dụng

Chạy server Laravel:

```bash
php artisan serve
```

Hoặc với XAMPP trên Windows:

```powershell
D:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

Mở trình duyệt:

```text
http://127.0.0.1:8000/login
```

## Luồng xử lí tồn kho theo đơn hàng

Khi tạo đơn mới ở trạng thái `Lên đơn`, số lượng trong kho chưa thay đổi.

Khi cập nhật trạng thái đơn sang `Đã gửi`, hệ thống trừ tồn kho theo số lượng của đơn. Nếu số lượng trong kho không đủ, hệ thống sẽ báo lỗi và không cập nhật trạng thái.

Khi cập nhật trạng thái từ `Đã gửi` sang `Thành công`, hệ thống hoàn lại số lượng về kho.

Nếu đơn đang ở trạng thái `Đã gửi` và người dùng sửa mã hàng hoặc số lượng, hệ thống tự đồng bộ lại tồn kho theo phần chênh lệch.

## Popup nhắc đơn đến ngày

Khi đăng nhập vào các trang quản trị, hệ thống tự kiểm tra:

- Đơn `Lên đơn` có `ngày lấy <= hôm nay`: nhắc xác nhận chuyển sang `Đã gửi`.
- Đơn `Đã gửi` có `ngày trả <= hôm nay`: nhắc xác nhận chuyển sang `Thành công`.

Mỗi nhắc việc có thể:

- Xác nhận để cập nhật trạng thái.
- Chọn `Không nhắc lại` để tắt nhắc cho đơn đó.
- Bấm `Để sau` để đóng popup tạm thời.

## Một số lệnh hữu ích

Chạy migration mới:

```bash
php artisan migrate
```

Chạy lại seed:

```bash
php artisan db:seed
```

Xóa cache view:

```bash
php artisan view:clear
```

Xem route:

```bash
php artisan route:list
```

## Đẩy code lên GitHub

Kiểm tra thay đổi:

```bash
git status
```

Thêm file cần commit:

```bash
git add .
```

Nếu không muốn đưa file nén hoặc file tạm lên GitHub, bỏ qua chúng trước khi commit:

```bash
git restore --staged tiba_store.zip
```

Tạo commit:

```bash
git commit -m "Update project documentation"
```

Đẩy lên GitHub:

```bash
git push origin main
```

Remote hiện tại của dự án:

```text
https://github.com/nminhcuongdev/tibastore.git
```
