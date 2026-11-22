<?php

use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::get('/vouchers', [VoucherController::class, 'index'])->name('api.vouchers.index');
Route::get('/vouchers/{voucher}', [VoucherController::class, 'show'])->whereNumber('voucher')->name('api.vouchers.show');