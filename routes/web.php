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
use App\Http\Controllers\ReservationController;

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

Route::prefix('reservations')->name('reservations.')->group(function (): void {
    Route::get('/', [ReservationController::class, 'index'])->name('index');
    Route::get('/create', [ReservationController::class, 'create'])->name('create');
    Route::post('/', [ReservationController::class, 'store'])->name('store');
    Route::get('/{code}', [ReservationController::class, 'show'])->name('show');
    Route::patch('/{code}/cancel', [ReservationController::class, 'cancel'])->name('cancel');
    Route::patch('/{code}/status', [ReservationController::class, 'updateStatus'])->name('update-status');
});