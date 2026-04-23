<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductionPlanController;
use App\Http\Controllers\DailyReportController;
Route::get('/', function () {
    return view('master');
});
Route::get('/plans', [ProductionPlanController::class, 'index']);
Route::get('/report', [DailyReportController::class, 'index']);