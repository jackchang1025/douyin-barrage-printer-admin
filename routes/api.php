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
*/

// 公开路由（无需认证）
Route::prefix('auth')->group(function () {
    // 邮箱密码登录
    Route::post('/login', [AuthController::class, 'login']);
    
    // 手机号+密码登录
    Route::post('/login-phone', [AuthController::class, 'loginWithPhone']);
    
    // 手机号+验证码登录
    Route::post('/login-code', [AuthController::class, 'loginWithCode']);
    
    // 发送验证码
    Route::post('/send-code', [AuthController::class, 'sendCode']);
    
    // 用户注册
    Route::post('/register', [AuthController::class, 'register']);
});

// 需要认证的路由
Route::middleware('auth:sanctum')->group(function () {
    // 认证相关
    Route::prefix('auth')->group(function () {
        // 退出登录
        Route::post('/logout', [AuthController::class, 'logout']);
        
        // 获取当前用户信息
        Route::get('/me', [AuthController::class, 'me']);
    });

    // 订阅相关
    Route::prefix('subscription')->group(function () {
        // 检查订阅状态
        Route::get('/check', [AuthController::class, 'checkSubscription']);
    });
});

