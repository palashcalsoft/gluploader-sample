<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/minio-test', [App\Http\Controllers\MinioTestController::class, 'store']);
Route::get('/minio-test', [App\Http\Controllers\MinioTestController::class, 'index']);
