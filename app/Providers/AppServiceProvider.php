<?php

namespace App\Providers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer([
            'products.index',
            'products.show',
            'orders.index',
            'orders.form',
            'stock_import_histories.index',
        ], function ($view) {
            if (! Auth::check()) {
                $view->with('orderReminders', collect());
                return;
            }

            $today = now()->toDateString();

            $reminders = Order::query()
                ->with(['product', 'items.product'])
                ->where(function ($query) use ($today) {
                    $query->where('status', 'len_don')
                        ->where('pickup_date', '<=', $today)
                        ->where('pickup_reminder_dismissed', false);
                })
                ->orWhere(function ($query) use ($today) {
                    $query->where('status', 'da_gui')
                        ->where('return_date', '<=', $today)
                        ->where('return_reminder_dismissed', false);
                })
                ->orderBy('pickup_date')
                ->orderBy('return_date')
                ->limit(12)
                ->get()
                ->map(function (Order $order) {
                    $type = $order->status === 'len_don' ? 'pickup' : 'return';

                    return [
                        'order' => $order,
                        'type' => $type,
                        'title' => $type === 'pickup'
                            ? 'Đến ngày lấy hàng'
                            : 'Đến ngày trả hàng',
                        'message' => $type === 'pickup'
                            ? 'Xác nhận để cập nhật trạng thái đơn sang Đã gửi và trừ số lượng trong kho.'
                            : 'Xác nhận để cập nhật trạng thái đơn sang Thành công và hoàn số lượng về kho.',
                        'date' => $type === 'pickup'
                            ? $order->pickup_date
                            : $order->return_date,
                    ];
                });

            $view->with('orderReminders', $reminders);
        });
    }
}
