<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Auth\AccountDisabledException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvalidVerificationCodeException;
use App\Exceptions\Auth\PhoneAlreadyRegisteredException;
use App\Exceptions\Auth\SmsRateLimitException;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\VerificationCode;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * 会员认证控制器
 *
 * 提供会员登录、注册、验证码发送等功能
 */
class AuthController extends Controller
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    /**
     * 手机号+密码登录
     *
     * POST /api/auth/login-phone
     *
     * @throws InvalidCredentialsException 手机号或密码错误
     * @throws AccountDisabledException 账号被禁用
     */
    public function loginWithPhone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'countryCode' => 'required|string',
            'phone' => 'required|string',
            'password' => 'required|string|min:6',
        ], [
            'countryCode.required' => '请选择国家/地区',
            'phone.required' => '请输入手机号码',
            'password.required' => '请输入密码',
            'password.min' => '密码至少6位',
        ]);

        $member = Member::where('country_code', $validated['countryCode'])
            ->where('phone', $validated['phone'])
            ->first();

        // 验证用户存在且密码正确
        if (!$member || !Hash::check($validated['password'], $member->password)) {
            throw new InvalidCredentialsException();
        }

        // 检查账号状态
        if (!$member->isActive()) {
            throw new AccountDisabledException();
        }

        // 记录登录信息
        $member->recordLogin($request->ip());

        // 创建 API Token
        $token = $member->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => '登录成功',
            'token' => $token,
            'user' => $this->formatMember($member),
        ]);
    }

    /**
     * 手机号+验证码登录
     *
     * POST /api/auth/login-code
     *
     * @throws InvalidVerificationCodeException 验证码错误或已过期
     * @throws AccountDisabledException 账号被禁用
     */
    public function loginWithCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
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
            $validated['countryCode'],
            $validated['phone'],
            $validated['code'],
            VerificationCode::TYPE_LOGIN
        );

        if (!$isValid) {
            throw new InvalidVerificationCodeException();
        }

        // 查找或创建会员
        $member = Member::where('country_code', $validated['countryCode'])
            ->where('phone', $validated['phone'])
            ->first();

        if (!$member) {
            // 验证码登录时如果会员不存在，自动创建
            $member = $this->createMember($validated['countryCode'], $validated['phone']);
        }

        // 检查账号状态
        if (!$member->isActive()) {
            throw new AccountDisabledException();
        }

        // 更新手机验证时间
        if (!$member->phone_verified_at) {
            $member->update(['phone_verified_at' => now()]);
        }

        // 记录登录信息
        $member->recordLogin($request->ip());

        // 创建 API Token
        $token = $member->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => '登录成功',
            'token' => $token,
            'user' => $this->formatMember($member),
        ]);
    }

    /**
     * 发送验证码
     *
     * POST /api/auth/send-code
     *
     * @throws SmsRateLimitException 发送频率限制
     * @throws \App\Exceptions\Sms\SmsSendFailedException 短信发送失败
     * @throws \App\Exceptions\Sms\SmsGatewayException 短信网关不可用
     */
    public function sendCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'countryCode' => 'required|string',
            'phone' => 'required|string',
            'type' => 'nullable|string|in:login,register,reset,change_phone,bind_phone,verify_phone',
        ], [
            'countryCode.required' => '请选择国家/地区',
            'phone.required' => '请输入手机号码',
        ]);

        $type = $validated['type'] ?? VerificationCode::TYPE_LOGIN;

        // 检查是否可以发送验证码（60秒间隔）
        if (!VerificationCode::canSend($validated['countryCode'], $validated['phone'], 60)) {
            throw new SmsRateLimitException();
        }

        // 创建验证码记录
        $verification = VerificationCode::createCode(
            $validated['countryCode'],
            $validated['phone'],
            $type
        );

        // 发送短信验证码（失败时会抛出异常，由框架统一处理）
        $fullPhone = $validated['countryCode'] . $validated['phone'];
        $this->smsService->sendVerificationCode(
            $fullPhone,
            $verification->code,
            $type
        );

        return response()->json([
            'message' => '验证码已发送',
            // 开发环境可以返回验证码，生产环境应该移除
            'code' => config('app.debug') ? $verification->code : null,
        ]);
    }

    /**
     * 会员注册
     *
     * POST /api/auth/register
     *
     * @throws PhoneAlreadyRegisteredException 手机号已注册
     * @throws InvalidVerificationCodeException 验证码错误或已过期
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
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
        $exists = Member::where('country_code', $validated['countryCode'])
            ->where('phone', $validated['phone'])
            ->exists();

        if ($exists) {
            throw new PhoneAlreadyRegisteredException();
        }

        // 验证验证码
        $isValid = VerificationCode::verify(
            $validated['countryCode'],
            $validated['phone'],
            $validated['code'],
            VerificationCode::TYPE_REGISTER
        );

        if (!$isValid) {
            throw new InvalidVerificationCodeException();
        }

        // 创建会员
        $member = Member::create([
            'nickname' => '用户' . substr($validated['phone'], -4),
            'country_code' => $validated['countryCode'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'phone_verified_at' => now(),
            'plan' => 'free',
            'status' => Member::STATUS_ACTIVE,
        ]);

        // 创建免费订阅
        Subscription::createForMember($member, Subscription::PLAN_FREE);

        return response()->json([
            'message' => '注册成功',
        ]);
    }

    /**
     * 退出登录
     *
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
     * 获取当前会员信息
     *
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->formatMember($request->user()),
        ]);
    }

    /**
     * 检查订阅状态
     *
     * GET /api/subscription/check
     */
    public function checkSubscription(Request $request): JsonResponse
    {
        $member = $request->user();
        $features = $member->getSubscriptionFeatures();

        return response()->json([
            'active' => $member->isSubscriptionActive(),
            'plan' => $member->plan,
            'expiry_date' => $member->subscription_expiry?->toIso8601String(),
            'days_remaining' => $member->subscription_days_remaining,
            'features' => $features,
        ]);
    }

    /**
     * 创建新会员（验证码登录时自动创建）
     */
    private function createMember(string $countryCode, string $phone): Member
    {
        $member = Member::create([
            'nickname' => '用户' . substr($phone, -4),
            'country_code' => $countryCode,
            'phone' => $phone,
            'password' => Hash::make(str()->random(16)), // 随机密码
            'phone_verified_at' => now(),
            'plan' => 'free',
            'status' => Member::STATUS_ACTIVE,
        ]);

        // 创建免费订阅
        Subscription::createForMember($member, Subscription::PLAN_FREE);

        return $member;
    }

    /**
     * 格式化会员信息
     */
    private function formatMember(Member $member): array
    {
        return [
            'id' => $member->id,
            'nickname' => $member->nickname,
            'country_code' => $member->country_code,
            'phone' => $member->phone,
            'avatar' => $member->avatar,
            'plan' => $member->plan,
            'subscription_expiry' => $member->subscription_expiry?->toIso8601String(),
            'phone_verified_at' => $member->phone_verified_at?->toIso8601String(),
            'status' => $member->status,
            'created_at' => $member->created_at->toIso8601String(),
        ];
    }
}
