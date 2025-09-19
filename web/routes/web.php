<?php

use App\Http\Controllers\TestingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/testing', [App\Http\Controllers\TestingController::class, 'index']);
Route::post('/testing/csv/upload', [TestingController::class, 'uploadCsv']);
