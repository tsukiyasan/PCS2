<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductionPlanController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\StockController;
Route::get('/', function () {
    return view('master');
});
Route::get('/plans', [ProductionPlanController::class, 'index']);
Route::get('/report', [DailyReportController::class, 'index']);
Route::get('/stock', [StockController::class, 'index']);
Route::get('/suban_stock', [SubanStockController::class, 'index']);