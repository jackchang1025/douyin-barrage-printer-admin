<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 这里定义应用程序的 API 路由。这些路由会自动添加 /api 前缀。
| 
| 所有 API 路由使用 Member 模型（前端会员用户），
| User 模型仅用于 Filament 后台管理员。
|
*/

// 公开路由（无需认证）
Route::prefix('auth')->group(function () {
    // 手机号+密码登录
    Route::post('/login-phone', [AuthController::class, 'loginWithPhone']);

    // 手机号+验证码登录
    Route::post('/login-code', [AuthController::class, 'loginWithCode']);

    // 发送验证码
    Route::post('/send-code', [AuthController::class, 'sendCode']);

    // 会员注册
    Route::post('/register', [AuthController::class, 'register']);
});

// 需要认证的路由（使用 member guard）
Route::middleware('auth:member')->group(function () {
    // 认证相关
    Route::prefix('auth')->group(function () {
        // 退出登录
        Route::post('/logout', [AuthController::class, 'logout']);

        // 获取当前会员信息
        Route::get('/me', [AuthController::class, 'me']);
    });

    // 订阅相关
    Route::prefix('subscription')->group(function () {
        // 检查订阅状态
        Route::get('/check', [AuthController::class, 'checkSubscription']);
    });
});
