<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// 客户端下载
Route::get('/download', [DownloadController::class, 'latest'])->name('download.latest');
Route::get('/download/info', [DownloadController::class, 'info'])->name('download.info');
