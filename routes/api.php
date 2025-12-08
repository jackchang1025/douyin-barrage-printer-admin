<?php

use App\Http\Controllers\Api\AppVersionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SettingController;
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

// 应用版本管理（公开 + 上传需要 Token）
Route::prefix('app')->group(function () {
    // electron-updater 需要的 latest.yml
    Route::get('/latest.yml', [AppVersionController::class, 'latestYml']);
    // 获取最新版本信息（JSON）
    Route::get('/latest', [AppVersionController::class, 'latest']);
    // 下载安装包（按版本号）
    Route::get('/download/{version}', [AppVersionController::class, 'download'])->name('api.app.download');
    // 下载安装包（按文件名，electron-updater 使用）
    Route::get('/{fileName}', [AppVersionController::class, 'downloadByFileName'])
        ->where('fileName', '.*\.(exe|dmg|AppImage|zip)$');
    // 上传新版本（需要 X-Upload-Token）
    Route::post('/upload', [AppVersionController::class, 'upload']);
});

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

// 公开设置接口
Route::prefix('settings')->group(function () {
    // 获取联系方式设置
    Route::get('/contact', [SettingController::class, 'getContactSettings']);
});

// 需要认证的路由（使用 member guard）
Route::middleware('auth:member')->group(function () {
    // 认证相关
    Route::prefix('auth')->group(function () {
        // 退出登录
        Route::post('/logout', [AuthController::class, 'logout']);

        // 获取当前会员信息
        Route::get('/me', [AuthController::class, 'me']);

        // 验证 Token 有效性（单点登录检测）
        Route::get('/validate-token', [AuthController::class, 'validateToken']);
    });

    // 订阅相关
    Route::prefix('subscription')->group(function () {
        // 检查订阅状态
        Route::get('/check', [AuthController::class, 'checkSubscription']);
    });
});
