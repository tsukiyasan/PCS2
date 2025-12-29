<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductionPlanController;
Route::get('/', function () {
    return view('welcome');
});
Route::get('/plans', [ProductionPlanController::class, 'index']);