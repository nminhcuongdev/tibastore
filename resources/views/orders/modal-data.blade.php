{{-- Nguồn dữ liệu dùng chung cho các modal của đơn hàng.
     Trước đây mỗi dòng bảng nhúng sẵn 2 khối JSON; nay chỉ tải khi thực sự mở modal. --}}
<script>
    (function () {
        const URL_TEMPLATE = @json(route('orders.modal-data', ['order' => '__ID__']));
        const cache = new Map();

        // Tải một lần cho mỗi đơn rồi giữ lại: mở đi mở lại không gọi mạng thêm.
        window.loadOrderModalData = function (orderId) {
            const key = String(orderId);

            if (cache.has(key)) {
                return cache.get(key);
            }

            const promise = fetch(URL_TEMPLATE.replace('__ID__', key), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Không tải được dữ liệu đơn hàng.');
                }

                return response.json();
            }).catch(function (error) {
                // Hỏng thì bỏ cache để lần bấm sau còn thử lại được.
                cache.delete(key);
                throw error;
            });

            cache.set(key, promise);

            return promise;
        };

        // Sửa đơn xong quay lại danh sách thì dữ liệu cũ trong cache không còn đúng.
        window.clearOrderModalData = function (orderId) {
            if (orderId === undefined) {
                cache.clear();
                return;
            }

            cache.delete(String(orderId));
        };
    })();
</script>
