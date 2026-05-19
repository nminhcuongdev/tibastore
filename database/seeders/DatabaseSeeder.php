<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['code' => 'admin'],
            [
                'password' => 'password',
                'name' => 'Admin User',
                'role' => 'admin',
                'delflag' => false,
            ]
        );

        $products = [
            ['code' => 'VAY001', 'name' => 'Váy hoa pastel', 'stock_quantity' => 18, 'fabric' => 'Voan lụa', 'size' => 'S-M', 'import_price' => 185000],
            ['code' => 'AO002', 'name' => 'Áo sơ mi cổ nơ', 'stock_quantity' => 24, 'fabric' => 'Cotton lụa', 'size' => 'M', 'import_price' => 125000],
            ['code' => 'CHAN003', 'name' => 'Chân váy xếp ly', 'stock_quantity' => 12, 'fabric' => 'Tuytsi', 'size' => 'M-L', 'import_price' => 155000],
            ['code' => 'DAM004', 'name' => 'Đầm công sở be hồng', 'stock_quantity' => 9, 'fabric' => 'Kate mềm', 'size' => 'L', 'import_price' => 210000],
            ['code' => 'SET005', 'name' => 'Set blazer nữ tính', 'stock_quantity' => 7, 'fabric' => 'Tweed', 'size' => 'Free size', 'import_price' => 320000],
            ['code' => 'QUAN006', 'name' => 'Quần ống suông kem', 'stock_quantity' => 16, 'fabric' => 'Linen', 'size' => 'S', 'import_price' => 170000],
            ['code' => 'AO007', 'name' => 'Áo cardigan mỏng', 'stock_quantity' => 20, 'fabric' => 'Len dệt kim', 'size' => 'Free size', 'import_price' => 145000],
            ['code' => 'VAY008', 'name' => 'Váy maxi lụa trắng', 'stock_quantity' => 5, 'fabric' => 'Lụa satin', 'size' => 'M', 'import_price' => 260000],
            ['code' => 'DAM009', 'name' => 'Đầm hai dây hoa nhí', 'stock_quantity' => 14, 'fabric' => 'Rayon', 'size' => 'S', 'import_price' => 175000],
            ['code' => 'AO010', 'name' => 'Áo croptop ren', 'stock_quantity' => 22, 'fabric' => 'Ren cotton', 'size' => 'M', 'import_price' => 98000],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                [
                    'code' => $product['code'],
                    'size' => $product['size'],
                ],
                $product
            );
        }

        $orders = [
            ['order_name' => 'Đơn chụp lookbook nắng tháng 5', 'closer_name' => 'Linh', 'product_code' => 'VAY001', 'product_size' => 'S-M', 'quantity' => 2, 'pickup_date' => '2026-05-18', 'event_date' => '2026-05-19', 'return_date' => '2026-05-20'],
            ['order_name' => 'Đơn đi tiệc pastel', 'closer_name' => 'Mai', 'product_code' => 'DAM004', 'product_size' => 'L', 'quantity' => 1, 'pickup_date' => '2026-05-19', 'event_date' => '2026-05-20', 'return_date' => '2026-05-21'],
            ['order_name' => 'Đơn studio set blazer', 'closer_name' => 'Trang', 'product_code' => 'SET005', 'product_size' => 'Free size', 'quantity' => 1, 'pickup_date' => '2026-05-21', 'event_date' => '2026-05-22', 'return_date' => '2026-05-23'],
            ['order_name' => 'Đơn quay video áo cổ nơ', 'closer_name' => 'Linh', 'product_code' => 'AO002', 'product_size' => 'M', 'quantity' => 3, 'pickup_date' => '2026-05-22', 'event_date' => '2026-05-23', 'return_date' => '2026-05-24'],
            ['order_name' => 'Đơn maxi ngoại cảnh', 'closer_name' => 'Hà', 'product_code' => 'VAY008', 'product_size' => 'M', 'quantity' => 1, 'pickup_date' => '2026-05-24', 'event_date' => '2026-05-25', 'return_date' => '2026-05-26'],
            ['order_name' => 'Đơn ảnh sản phẩm cardigan', 'closer_name' => 'Nhi', 'product_code' => 'AO007', 'product_size' => 'Free size', 'quantity' => 2, 'pickup_date' => '2026-05-25', 'event_date' => '2026-05-26', 'return_date' => '2026-05-27'],
            ['order_name' => 'Đơn chân váy xếp ly', 'closer_name' => 'Mai', 'product_code' => 'CHAN003', 'product_size' => 'M-L', 'quantity' => 2, 'pickup_date' => '2026-05-27', 'event_date' => '2026-05-28', 'return_date' => '2026-05-29'],
            ['order_name' => 'Đơn fitting linen kem', 'closer_name' => 'An', 'product_code' => 'QUAN006', 'product_size' => 'S', 'quantity' => 1, 'pickup_date' => '2026-05-28', 'event_date' => '2026-05-29', 'return_date' => '2026-05-30'],
            ['order_name' => 'Đơn chụp hoa nhí', 'closer_name' => 'Trang', 'product_code' => 'DAM009', 'product_size' => 'S', 'quantity' => 2, 'pickup_date' => '2026-06-01', 'event_date' => '2026-06-02', 'return_date' => '2026-06-03'],
            ['order_name' => 'Đơn croptop ren studio', 'closer_name' => 'Hà', 'product_code' => 'AO010', 'product_size' => 'M', 'quantity' => 4, 'pickup_date' => '2026-06-03', 'event_date' => '2026-06-04', 'return_date' => '2026-06-05'],
        ];

        $sampleProductIds = Product::whereIn('code', array_column($orders, 'product_code'))
            ->pluck('id');

        Order::whereIn('product_id', $sampleProductIds)
            ->whereBetween('pickup_date', ['2026-05-18', '2026-06-03'])
            ->delete();

        foreach ($orders as $order) {
            $product = Product::where('code', $order['product_code'])
                ->where('size', $order['product_size'])
                ->first();

            if (! $product) {
                continue;
            }

            Order::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'closer_name' => $order['closer_name'],
                    'pickup_date' => $order['pickup_date'],
                    'event_date' => $order['event_date'],
                    'return_date' => $order['return_date'],
                ],
                [
                    'order_name' => $order['order_name'],
                    'closer_name' => $order['closer_name'],
                    'pickup_date' => $order['pickup_date'],
                    'event_date' => $order['event_date'],
                    'return_date' => $order['return_date'],
                    'quantity' => $order['quantity'],
                    'status' => 'len_don',
                    'pickup_reminder_dismissed' => false,
                    'return_reminder_dismissed' => false,
                ]
            );
        }
    }
}
