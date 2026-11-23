<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MallController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\RoutePlannerController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VoucherController;

use App\Http\Controllers\ReservationController;


// Reservation
Route::get('/reservations', [ReservationController::class, 'index'])
    ->name('reservations.index');

Route::middleware(['auth', 'user'])->group(function () {
    Route::post('/reservations/{store}', [ReservationController::class, 'store'])
        ->name('reservations.store');

    Route::get('/my-reservations', [ReservationController::class, 'mine'])
        ->name('reservations.mine');

    Route::patch('/my-reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
        ->name('reservations.cancel');
});

// Voucher
Route::get('/vouchers', [VoucherController::class, 'index'])
    ->name('vouchers.index');

Route::middleware(['auth', 'user'])->group(function () {
    Route::post('/vouchers/{voucher}/claim', [VoucherController::class, 'claim'])
        ->name('vouchers.claim');

    Route::get('/vouchers/{voucher}/checkout', [VoucherController::class, 'checkout'])
        ->name('vouchers.checkout');

    Route::post('/vouchers/{voucher}/purchase', [VoucherController::class, 'purchase'])
        ->name('vouchers.purchase');

    Route::get('/my-vouchers', [VoucherController::class, 'myVouchers'])
        ->name('vouchers.mine');

    Route::post('/my-vouchers/{userVoucher}/pay', [VoucherController::class, 'pay'])
        ->name('vouchers.pay');
});

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/team', 'team')->name('team');

Route::get('/malls', [MallController::class, 'index'])->name('malls.index');
Route::get('/malls/compare', [MallController::class, 'compare'])->name('malls.compare');
Route::get('/malls/{id}', [MallController::class, 'show'])->where('id', '[a-z0-9_]+')->name('malls.show');

Route::get('/search', [SearchController::class, 'index'])->name('search.index');
Route::match(['get', 'post'], '/search/filter', [SearchController::class, 'filter'])->name('search.filter');

Route::get('/map', [MapController::class, 'index'])->name('map.index');
Route::get('/map/nearby', [MapController::class, 'nearby'])->middleware('throttle:20,1')->name('map.nearby');

Route::get('/planner', [RoutePlannerController::class, 'index'])->name('planner.index');
Route::post('/planner/calculate', [RoutePlannerController::class, 'calculate'])->name('planner.calculate');
Route::get('/planner/export/pdf', [RoutePlannerController::class, 'exportPdf'])->name('planner.export.pdf');
Route::get('/planner/export/ics', [RoutePlannerController::class, 'exportIcs'])->name('planner.export.ics');

Route::get('/recommend', [RecommendationController::class, 'recommend'])->name('recommendation.index');
Route::get('/budget', [BudgetController::class, 'index'])->name('budget.index');
Route::get('/budget/estimate', [BudgetController::class, 'estimate'])->name('budget.estimate');

Route::post('/chatbot', [ChatbotController::class, 'chat'])->middleware('throttle:10,1')->name('chatbot.chat');
Route::get('/chatbot/history', [ChatbotController::class, 'history'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('chatbot.history');

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register'])
        ->name('register.store');

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

    // User Itineraries
Route::middleware(['auth', 'user'])->group(function () {
    Route::get('/my-itineraries', [ItineraryController::class, 'index'])
        ->name('itineraries.index');

    Route::get('/my-itineraries/{id}', [ItineraryController::class, 'show'])
        ->whereNumber('id')
        ->name('itineraries.show');

    Route::delete('/my-itineraries/{id}', [ItineraryController::class, 'destroy'])
        ->whereNumber('id')
        ->name('itineraries.destroy');
});
// Admin Dashboard & Management
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])
        ->name('dashboard');

    Route::resource('/vouchers', \App\Http\Controllers\Admin\VoucherController::class);

    Route::get('/reservations', [\App\Http\Controllers\Admin\ReservationController::class, 'index'])
    ->name('reservations.index');

Route::patch('/reservations/{reservation}', [\App\Http\Controllers\Admin\ReservationController::class, 'update'])
    ->name('reservations.update');
});