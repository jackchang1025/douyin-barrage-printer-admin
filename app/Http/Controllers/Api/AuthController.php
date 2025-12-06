<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCode;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * 邮箱密码登录
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => '请输入邮箱',
            'email.email' => '邮箱格式不正确',
            'password.required' => '请输入密码',
            'password.min' => '密码至少6位',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => '邮箱或密码错误',
            ], 401);
        }

        // 创建 API Token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * 手机号+密码登录
     * POST /api/auth/login-phone
     */
    public function loginWithPhone(Request $request): JsonResponse
    {
        $request->validate([
            'countryCode' => 'required|string',
            'phone' => 'required|string',
            'password' => 'required|string|min:6',
        ], [
            'countryCode.required' => '请选择国家/地区',
            'phone.required' => '请输入手机号码',
            'password.required' => '请输入密码',
            'password.min' => '密码至少6位',
        ]);

        $user = User::where('country_code', $request->countryCode)
            ->where('phone', $request->phone)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => '手机号或密码错误',
            ], 401);
        }

        // 创建 API Token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * 手机号+验证码登录
     * POST /api/auth/login-code
     */
    public function loginWithCode(Request $request): JsonResponse
    {
        $request->validate([
            'countryCode' => 'required|string',
            'phone' => 'required|string',
            'code' => 'required|string',
        ], [
            'countryCode.required' => '请选择国家/地区',
            'phone.required' => '请输入手机号码',
            'code.required' => '请输入验证码',
        ]);

        // 验证验证码
        $isValid = VerificationCode::verify(
            $request->countryCode,
            $request->phone,
            $request->code,
            VerificationCode::TYPE_LOGIN
        );

        if (!$isValid) {
            return response()->json([
                'message' => '验证码不正确或已过期',
            ], 400);
        }

        // 查找或创建用户
        $user = User::where('country_code', $request->countryCode)
            ->where('phone', $request->phone)
            ->first();

        if (!$user) {
            // 验证码登录时如果用户不存在，自动创建
            $user = User::create([
                'name' => $request->countryCode . $request->phone,
                'country_code' => $request->countryCode,
                'phone' => $request->phone,
                'password' => Hash::make(str()->random(16)), // 随机密码
                'phone_verified_at' => now(),
                'plan' => 'free',
            ]);

            // 创建免费订阅
            Subscription::createForUser($user, Subscription::PLAN_FREE);
        }

        // 更新手机验证时间
        if (!$user->phone_verified_at) {
            $user->update(['phone_verified_at' => now()]);
        }

        // 创建 API Token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * 发送验证码
     * POST /api/auth/send-code
     */
    public function sendCode(Request $request): JsonResponse
    {
        $request->validate([
            'countryCode' => 'required|string',
            'phone' => 'required|string',
            'type' => 'nullable|string|in:login,register,reset',
        ], [
            'countryCode.required' => '请选择国家/地区',
            'phone.required' => '请输入手机号码',
        ]);

        $type = $request->type ?? VerificationCode::TYPE_LOGIN;

        // 检查是否可以发送验证码（60秒间隔）
        if (!VerificationCode::canSend($request->countryCode, $request->phone, 60)) {
            return response()->json([
                'message' => '请稍后再试',
            ], 429);
        }

        // 创建验证码
        $verification = VerificationCode::createCode(
            $request->countryCode,
            $request->phone,
            $type
        );

        // TODO: 实际发送短信
        // $this->smsService->send($request->countryCode . $request->phone, $verification->code);

        Log::info("验证码发送", [
            'phone' => $request->countryCode . $request->phone,
            'code' => $verification->code,
        ]);

        return response()->json([
            'message' => '验证码已发送',
            // 开发环境可以返回验证码，生产环境应该移除
            'code' => config('app.debug') ? $verification->code : null,
        ]);
    }

    /**
     * 用户注册
     * POST /api/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'countryCode' => 'required|string',
            'phone' => 'required|string',
            'password' => 'required|string|min:6',
            'code' => 'required|string',
        ], [
            'countryCode.required' => '请选择国家/地区',
            'phone.required' => '请输入手机号码',
            'password.required' => '请输入密码',
            'password.min' => '密码至少6位',
            'code.required' => '请输入验证码',
        ]);

        // 检查手机号是否已注册
        $exists = User::where('country_code', $request->countryCode)
            ->where('phone', $request->phone)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => '该手机号码已注册',
            ], 400);
        }

        // 验证验证码
        $isValid = VerificationCode::verify(
            $request->countryCode,
            $request->phone,
            $request->code,
            VerificationCode::TYPE_REGISTER
        );

        if (!$isValid) {
            return response()->json([
                'message' => '验证码不正确或已过期',
            ], 400);
        }

        // 创建用户
        $user = User::create([
            'name' => $request->countryCode . $request->phone,
            'country_code' => $request->countryCode,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'phone_verified_at' => now(),
            'plan' => 'free',
        ]);

        // 创建免费订阅
        Subscription::createForUser($user, Subscription::PLAN_FREE);

        return response()->json([
            'message' => '注册成功',
        ]);
    }

    /**
     * 退出登录
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        // 删除当前 Token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => '已退出登录',
        ]);
    }

    /**
     * 获取当前用户信息
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->formatUser($request->user()),
        ]);
    }

    /**
     * 检查订阅状态
     * GET /api/subscription/check
     */
    public function checkSubscription(Request $request): JsonResponse
    {
        $user = $request->user();
        $features = $user->getSubscriptionFeatures();

        return response()->json([
            'active' => $user->isSubscriptionActive(),
            'plan' => $user->plan,
            'expiry_date' => $user->subscription_expiry?->toIso8601String(),
            'days_remaining' => $user->subscription_days_remaining,
            'features' => $features,
        ]);
    }

    /**
     * 格式化用户信息
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'country_code' => $user->country_code,
            'phone' => $user->phone,
            'plan' => $user->plan,
            'subscription_expiry' => $user->subscription_expiry?->toIso8601String(),
            'phone_verified_at' => $user->phone_verified_at?->toIso8601String(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at->toIso8601String(),
        ];
    }
}

