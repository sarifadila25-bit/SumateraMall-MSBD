<?php

use App\Http\Controllers\AdminVoucherController;
use App\Http\Controllers\VoucherController;
use App\Http\Middleware\EnsureAdminToken;
use Illuminate\Support\Facades\Route;

Route::get('/vouchers', [VoucherController::class, 'index'])->name('api.vouchers.index');
Route::get('/vouchers/{voucher}', [VoucherController::class, 'show'])->whereNumber('voucher')->name('api.vouchers.show');

Route::middleware('throttle:30,1')->group(function () {
    Route::post('/vouchers/{voucher}/claim', [VoucherController::class, 'claim'])->whereNumber('voucher')->name('api.vouchers.claim');
    Route::post('/vouchers/{voucher}/purchase', [VoucherController::class, 'purchase'])->whereNumber('voucher')->name('api.vouchers.purchase');
    Route::post('/user-vouchers/{userVoucher}/redeem', [VoucherController::class, 'redeem'])->whereNumber('userVoucher')->name('api.uservouchers.redeem');
});

Route::get('/voucher-history', [VoucherController::class, 'history'])->name('api.vouchers.history');

// CRUD admin (dijaga token sementara, menunggu keputusan tim soal peran admin)
Route::prefix('admin')->middleware(EnsureAdminToken::class)->group(function () {
    Route::apiResource('vouchers', AdminVoucherController::class)
        ->parameters(['vouchers' => 'voucher'])
        ->whereNumber('voucher')
        ->names('api.admin.vouchers');
});