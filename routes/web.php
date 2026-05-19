<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderReminderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockImportHistoryController;
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

    Route::get('/products/{product}/track', [ProductController::class, 'track'])->name('products.track');
    Route::resource('products', ProductController::class);
    Route::resource('orders', OrderController::class)->except(['show']);
    Route::post('/order-reminders/{order}/confirm', [OrderReminderController::class, 'confirm'])
        ->name('order-reminders.confirm');
    Route::post('/order-reminders/{order}/dismiss', [OrderReminderController::class, 'dismiss'])
        ->name('order-reminders.dismiss');
    Route::get('/stock-import-histories', [StockImportHistoryController::class, 'index'])
        ->name('stock-import-histories.index');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
