<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarrierDirectoryController;
use App\Http\Controllers\DraftInvoiceController;
use App\Http\Controllers\ChangeLogController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderReminderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockImportHistoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkshopOrderController;
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
Route::get('/brand/tiba-logo', [DraftInvoiceController::class, 'logo'])->name('brand.logo');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/personal-edits/{section}/{direction}', [\App\Http\Controllers\PersonalEditController::class, 'replay'])
        ->name('personal-edits.replay');
    Route::get('/carrier-directory', [CarrierDirectoryController::class, 'index'])->name('carrier-directory.index');
    Route::get('/draft-invoices/logo', [DraftInvoiceController::class, 'logo'])->name('draft-invoices.logo');
    Route::resource('draft-invoices', DraftInvoiceController::class)->except(['show']);
    Route::get('/dashboard', function () {
        return redirect()->route('products.index');
    })->name('dashboard');

    Route::get('/products/daily-stock', [ProductController::class, 'inventoryCalendar'])->name('products.daily-stock');
    Route::get('/products/{product}/track', [ProductController::class, 'track'])->name('products.track');
    // Phai dat truoc Route::resource de khong bi nuot boi products/{product}.
    Route::patch('/products/{product}/discontinued', [ProductController::class, 'toggleDiscontinued'])
        ->name('products.discontinued');
    Route::resource('products', ProductController::class);
    Route::post('/orders/availability', [OrderController::class, 'availability'])->name('orders.availability');
    Route::get('/orders/customer-suggestions', [OrderController::class, 'customerSuggestions'])->name('orders.customer-suggestions');
    Route::get('/orders/{order}/modal-data', [OrderController::class, 'modalData'])->name('orders.modal-data');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('/orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->name('orders.payment-status');
    Route::resource('orders', OrderController::class);
    Route::post('/order-reminders/{order}/confirm', [OrderReminderController::class, 'confirm'])
        ->name('order-reminders.confirm');
    Route::post('/order-reminders/{order}/dismiss', [OrderReminderController::class, 'dismiss'])
        ->name('order-reminders.dismiss');
    Route::post('/order-reminders/preferences', [OrderReminderController::class, 'preferences'])
        ->name('order-reminders.preferences');
    Route::get('/stock-import-histories', [StockImportHistoryController::class, 'index'])
        ->name('stock-import-histories.index');
    Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('/change-logs', [ChangeLogController::class, 'index'])->name('change-logs.index');
    Route::patch('/workshop-orders/{workshopOrder}/status', [WorkshopOrderController::class, 'updateStatus'])
        ->name('workshop-orders.status');
    Route::post('/workshop-orders/{workshopOrder}/stock', [WorkshopOrderController::class, 'pushToStock'])
        ->name('workshop-orders.stock');
    Route::post('/workshop-orders/{workshopOrder}/update-customer-order', [WorkshopOrderController::class, 'updateCustomerOrder'])
        ->name('workshop-orders.update-customer-order');
    Route::post('/workshop-stock-transfers/{transfer}/undo', [WorkshopOrderController::class, 'undoStockTransfer'])
        ->name('workshop-orders.undo-stock');
    Route::delete('/workshop-stock-transfers/{transfer}', [WorkshopOrderController::class, 'deleteStockTransfer'])
        ->name('workshop-orders.delete-stock-history');
    Route::post('/workshop-orders/undo-last-edit', [WorkshopOrderController::class, 'undoLastEdit'])->name('workshop-orders.undo-last-edit');
    Route::post('/workshop-orders/redo-last-edit', [WorkshopOrderController::class, 'redoLastEdit'])->name('workshop-orders.redo-last-edit');
    Route::post('/workshop-orders/create-product', [WorkshopOrderController::class, 'createProduct'])->name('workshop-orders.create-product');
    Route::get('/workshop-orders/suggest-product-code', [WorkshopOrderController::class, 'suggestProductCode'])->name('workshop-orders.suggest-product-code');
    Route::resource('workshop-orders', WorkshopOrderController::class)->except(['show']);

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
