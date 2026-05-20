<?php

namespace App\Providers;

use App\Services\OrderInventoryService;
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
            'orders.show',
            'stock_import_histories.index',
        ], function ($view) {
            if (Auth::check()) {
                app(OrderInventoryService::class)->syncDueOrders();
            }

            $view->with('orderReminders', collect());
        });
    }
}
