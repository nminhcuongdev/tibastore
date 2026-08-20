<?php

namespace App\Providers;

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
        // Truoc day composer nay con goi syncDueOrders(), trong khi cac controller
        // tuong ung cung da goi -> moi lan mo trang chay hai lan. Viec dong bo nay
        // gio thuoc ve controller, composer chi con cap bien cho view.
        View::composer([
            'products.index',
            'products.show',
            'orders.index',
            'orders.form',
            'orders.show',
            'stock_import_histories.index',
        ], function ($view) {
            $view->with('orderReminders', collect());
        });
    }
}
