<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChangeLogController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderReminderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockImportHistoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('products.index');
    })->name('dashboard');

    Route::get('/products/daily-stock', [ProductController::class, 'inventoryCalendar'])->name('products.daily-stock');
    Route::get('/products/{product}/track', [ProductController::class, 'track'])->name('products.track');
    Route::resource('products', ProductController::class);
    Route::post('/orders/availability', [OrderController::class, 'availability'])->name('orders.availability');
    Route::get('/orders/{order}/modal-data', [OrderController::class, 'modalData'])->name('orders.modal-data');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('/orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->name('orders.payment-status');
    Route::resource('orders', OrderController::class);
    Route::post('/order-reminders/{order}/confirm', [OrderReminderController::class, 'confirm'])
        ->name('order-reminders.confirm');
    Route::post('/order-reminders/{order}/dismiss', [OrderReminderController::class, 'dismiss'])
        ->name('order-reminders.dismiss');
    Route::get('/stock-import-histories', [StockImportHistoryController::class, 'index'])
        ->name('stock-import-histories.index');
    Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('/change-logs', [ChangeLogController::class, 'index'])->name('change-logs.index');

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
