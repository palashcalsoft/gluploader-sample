<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\GLEntryUploadController;
use App\Http\Controllers\GLEntryQueryController;

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// App pages (protected)
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('home');
    })->name('home');

    Route::view('/uploader', 'uploader')->name('uploader');

    Route::post('/gl/upload', [GLEntryUploadController::class, 'store'])->name('gl.upload');
    Route::get('/gl/masters', [GLEntryQueryController::class, 'masters']);
    Route::get('/gl/masters/{id}', function (int $id) { return view('gl_details'); })->whereNumber('id');
    Route::get('/api/gl/masters/{id}', [GLEntryQueryController::class, 'details']);
    Route::post('/gl/masters/{id}/retry', [GLEntryQueryController::class, 'retryFailed']);
});
