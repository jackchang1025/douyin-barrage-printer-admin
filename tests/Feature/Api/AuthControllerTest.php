<?php

use App\Exceptions\Auth\AccountDisabledException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvalidVerificationCodeException;
use App\Exceptions\Auth\PhoneAlreadyRegisteredException;
use App\Exceptions\Auth\SmsRateLimitException;
use App\Exceptions\Sms\SmsGatewayException;
use App\Exceptions\Sms\SmsSendFailedException;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\VerificationCode;
use App\Services\SmsService;
use Illuminate\Support\Facades\Hash;

describe('AuthController', function () {

    /*
    |--------------------------------------------------------------------------
    | POST /api/auth/login-phone - 手机号密码登录
    |--------------------------------------------------------------------------
    */
    describe('POST /api/auth/login-phone', function () {

        it('使用正确的手机号密码登录成功', function () {
            $member = Member::factory()->create([
                'country_code' => '+86',
                'phone' => '13800138000',
                'password' => Hash::make('password123'),
            ]);

            $response = $this->postJson('/api/auth/login-phone', [
                'countryCode' => '+86',
                'phone' => '13800138000',
                'password' => 'password123',
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'token',
                    'user' => [
                        'id',
                        'nickname',
                        'country_code',
                        'phone',
                        'avatar',
                        'plan',
                        'subscription_expiry',
                        'phone_verified_at',
                        'status',
                        'created_at',
                    ],
                ])
                ->assertJsonPath('message', '登录成功')
                ->assertJsonPath('user.id', $member->id);

            // 验证登录时间已更新
            $member->refresh();
            expect($member->last_login_at)->not->toBeNull();
        });

        it('使用错误密码登录失败', function () {
            Member::factory()->create([
                'country_code' => '+86',
                'phone' => '13800138001',
                'password' => Hash::make('password123'),
            ]);

            $response = $this->postJson('/api/auth/login-phone', [
                'countryCode' => '+86',
                'phone' => '13800138001',
                'password' => 'wrongpassword',
            ]);

            $response->assertStatus(401)
                ->assertJson([
                    'message' => '手机号或密码错误',
                    'error_code' => 'INVALID_CREDENTIALS',
                ]);
        });

        it('使用不存在的手机号登录失败', function () {
            $response = $this->postJson('/api/auth/login-phone', [
                'countryCode' => '+86',
                'phone' => '13800138002',
                'password' => 'password123',
            ]);

            $response->assertStatus(401)
                ->assertJson([
                    'message' => '手机号或密码错误',
                    'error_code' => 'INVALID_CREDENTIALS',
                ]);
        });

        it('禁用的账号无法登录', function () {
            Member::factory()->disabled()->create([
                'country_code' => '+86',
                'phone' => '13800138003',
                'password' => Hash::make('password123'),
            ]);

            $response = $this->postJson('/api/auth/login-phone', [
                'countryCode' => '+86',
                'phone' => '13800138003',
                'password' => 'password123',
            ]);

            $response->assertStatus(403)
                ->assertJson([
                    'message' => '账号已被禁用，请联系客服',
                    'error_code' => 'ACCOUNT_DISABLED',
                ]);
        });

        it('缺少必填参数返回验证错误', function () {
            $response = $this->postJson('/api/auth/login-phone', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['countryCode', 'phone', 'password']);
        });

        it('密码少于6位返回验证错误', function () {
            $response = $this->postJson('/api/auth/login-phone', [
                'countryCode' => '+86',
                'phone' => '13800138004',
                'password' => '12345',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | POST /api/auth/login-code - 验证码登录
    |--------------------------------------------------------------------------
    */
    describe('POST /api/auth/login-code', function () {

        it('使用正确的验证码登录成功', function () {
            $member = Member::factory()->create([
                'country_code' => '+86',
                'phone' => '13800138010',
            ]);

            // 创建验证码
            $verification = VerificationCode::createCode('+86', '13800138010', 'login');

            $response = $this->postJson('/api/auth/login-code', [
                'countryCode' => '+86',
                'phone' => '13800138010',
                'code' => $verification->code,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure(['message', 'token', 'user'])
                ->assertJsonPath('message', '登录成功');

            // 验证验证码已被使用
            $verification->refresh();
            expect($verification->is_used)->toBeTrue();
        });

        it('使用错误的验证码登录失败', function () {
            Member::factory()->create([
                'country_code' => '+86',
                'phone' => '13800138011',
            ]);

            $response = $this->postJson('/api/auth/login-code', [
                'countryCode' => '+86',
                'phone' => '13800138011',
                'code' => '000000',
            ]);

            $response->assertStatus(400)
                ->assertJson([
                    'message' => '验证码不正确或已过期',
                    'error_code' => 'INVALID_VERIFICATION_CODE',
                ]);
        });

        it('使用过期的验证码登录失败', function () {
            Member::factory()->create([
                'country_code' => '+86',
                'phone' => '13800138012',
            ]);

            // 创建已过期的验证码
            $verification = VerificationCode::create([
                'country_code' => '+86',
                'phone' => '13800138012',
                'code' => '123456',
                'type' => 'login',
                'expires_at' => now()->subMinutes(1),
                'is_used' => false,
            ]);

            $response = $this->postJson('/api/auth/login-code', [
                'countryCode' => '+86',
                'phone' => '13800138012',
                'code' => '123456',
            ]);

            $response->assertStatus(400)
                ->assertJson([
                    'message' => '验证码不正确或已过期',
                    'error_code' => 'INVALID_VERIFICATION_CODE',
                ]);
        });

        it('新用户使用验证码登录自动创建账号', function () {
            // 确保用户不存在
            expect(Member::where('phone', '13800138013')->exists())->toBeFalse();

            // 创建验证码
            $verification = VerificationCode::createCode('+86', '13800138013', 'login');

            $response = $this->postJson('/api/auth/login-code', [
                'countryCode' => '+86',
                'phone' => '13800138013',
                'code' => $verification->code,
            ]);

            $response->assertStatus(200);

            // 验证用户已创建
            $member = Member::where('phone', '13800138013')->first();
            expect($member)->not->toBeNull();
            expect($member->plan)->toBe('free');
            expect($member->phone_verified_at)->not->toBeNull();

            // 验证订阅已创建
            expect($member->subscriptions()->exists())->toBeTrue();
        });

        it('禁用的账号使用验证码登录失败', function () {
            Member::factory()->disabled()->create([
                'country_code' => '+86',
                'phone' => '13800138014',
            ]);

            $verification = VerificationCode::createCode('+86', '13800138014', 'login');

            $response = $this->postJson('/api/auth/login-code', [
                'countryCode' => '+86',
                'phone' => '13800138014',
                'code' => $verification->code,
            ]);

            $response->assertStatus(403)
                ->assertJson([
                    'message' => '账号已被禁用，请联系客服',
                    'error_code' => 'ACCOUNT_DISABLED',
                ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | POST /api/auth/register - 用户注册
    |--------------------------------------------------------------------------
    */
    describe('POST /api/auth/register', function () {

        it('注册成功', function () {
            // 创建验证码
            $verification = VerificationCode::createCode('+86', '13800138020', 'register');

            $response = $this->postJson('/api/auth/register', [
                'countryCode' => '+86',
                'phone' => '13800138020',
                'password' => 'password123',
                'code' => $verification->code,
            ]);

            $response->assertStatus(200)
                ->assertJson(['message' => '注册成功']);

            // 验证用户已创建
            $member = Member::where('phone', '13800138020')->first();
            expect($member)->not->toBeNull();
            expect($member->plan)->toBe('free');
            expect($member->status)->toBe(Member::STATUS_ACTIVE);
            expect($member->phone_verified_at)->not->toBeNull();

            // 验证密码正确
            expect(Hash::check('password123', $member->password))->toBeTrue();

            // 验证订阅已创建
            expect($member->subscriptions()->exists())->toBeTrue();
        });

        it('重复注册失败', function () {
            Member::factory()->create([
                'country_code' => '+86',
                'phone' => '13800138021',
            ]);

            $verification = VerificationCode::createCode('+86', '13800138021', 'register');

            $response = $this->postJson('/api/auth/register', [
                'countryCode' => '+86',
                'phone' => '13800138021',
                'password' => 'password123',
                'code' => $verification->code,
            ]);

            $response->assertStatus(400)
                ->assertJson([
                    'message' => '该手机号码已注册',
                    'error_code' => 'PHONE_ALREADY_REGISTERED',
                ]);
        });

        it('使用错误的验证码注册失败', function () {
            $response = $this->postJson('/api/auth/register', [
                'countryCode' => '+86',
                'phone' => '13800138022',
                'password' => 'password123',
                'code' => '000000',
            ]);

            $response->assertStatus(400)
                ->assertJson([
                    'message' => '验证码不正确或已过期',
                    'error_code' => 'INVALID_VERIFICATION_CODE',
                ]);
        });

        it('密码少于6位注册失败', function () {
            $verification = VerificationCode::createCode('+86', '13800138023', 'register');

            $response = $this->postJson('/api/auth/register', [
                'countryCode' => '+86',
                'phone' => '13800138023',
                'password' => '12345',
                'code' => $verification->code,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('缺少验证码注册失败', function () {
            $response = $this->postJson('/api/auth/register', [
                'countryCode' => '+86',
                'phone' => '13800138024',
                'password' => 'password123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['code']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | POST /api/auth/send-code - 发送验证码
    |--------------------------------------------------------------------------
    */
    describe('POST /api/auth/send-code', function () {

        it('发送验证码成功', function () {
            // Mock SmsService
            $this->mock(SmsService::class, function ($mock) {
                $mock->shouldReceive('sendVerificationCode')
                    ->once()
                    ->andReturnNull();
            });

            $response = $this->postJson('/api/auth/send-code', [
                'countryCode' => '+86',
                'phone' => '13800138030',
                'type' => 'login',
            ]);

            $response->assertStatus(200)
                ->assertJson(['message' => '验证码已发送']);

            // 验证验证码已保存到数据库
            expect(VerificationCode::where('phone', '13800138030')->exists())->toBeTrue();
        });

        it('发送注册验证码成功', function () {
            $this->mock(SmsService::class, function ($mock) {
                $mock->shouldReceive('sendVerificationCode')
                    ->once()
                    ->andReturnNull();
            });

            $response = $this->postJson('/api/auth/send-code', [
                'countryCode' => '+86',
                'phone' => '13800138031',
                'type' => 'register',
            ]);

            $response->assertStatus(200)
                ->assertJson(['message' => '验证码已发送']);

            // 验证验证码类型正确
            $code = VerificationCode::where('phone', '13800138031')->first();
            expect($code->type)->toBe('register');
        });

        it('频繁发送验证码被限制', function () {
            // Mock SmsService
            $this->mock(SmsService::class, function ($mock) {
                $mock->shouldReceive('sendVerificationCode')
                    ->once()
                    ->andReturnNull();
            });

            // 第一次发送成功
            $this->postJson('/api/auth/send-code', [
                'countryCode' => '+86',
                'phone' => '13800138032',
                'type' => 'login',
            ])->assertStatus(200);

            // 立即第二次发送被限制
            $response = $this->postJson('/api/auth/send-code', [
                'countryCode' => '+86',
                'phone' => '13800138032',
                'type' => 'login',
            ]);

            $response->assertStatus(429)
                ->assertJson([
                    'message' => '请稍后再试',
                    'error_code' => 'SMS_RATE_LIMITED',
                ]);
        });

        it('短信网关不可用时返回错误', function () {
            // Mock SmsService 抛出异常
            $this->mock(SmsService::class, function ($mock) {
                $mock->shouldReceive('sendVerificationCode')
                    ->once()
                    ->andThrow(new SmsGatewayException('短信服务暂时不可用，请稍后重试', ['aliyun' => 'Gateway error']));
            });

            $response = $this->postJson('/api/auth/send-code', [
                'countryCode' => '+86',
                'phone' => '13800138033',
                'type' => 'login',
            ]);

            $response->assertStatus(503)
                ->assertJson([
                    'message' => '短信服务暂时不可用，请稍后重试',
                    'error_code' => 'SMS_GATEWAY_UNAVAILABLE',
                ]);
        });

        it('短信发送失败时返回错误', function () {
            // Mock SmsService 抛出异常
            $this->mock(SmsService::class, function ($mock) {
                $mock->shouldReceive('sendVerificationCode')
                    ->once()
                    ->andThrow(new SmsSendFailedException('短信发送失败，请稍后重试'));
            });

            $response = $this->postJson('/api/auth/send-code', [
                'countryCode' => '+86',
                'phone' => '13800138034',
                'type' => 'login',
            ]);

            $response->assertStatus(500)
                ->assertJson([
                    'message' => '短信发送失败，请稍后重试',
                    'error_code' => 'SMS_SEND_FAILED',
                ]);
        });

        it('缺少必填参数返回验证错误', function () {
            $response = $this->postJson('/api/auth/send-code', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['countryCode', 'phone']);
        });

        it('默认类型为 login', function () {
            $this->mock(SmsService::class, function ($mock) {
                $mock->shouldReceive('sendVerificationCode')
                    ->once()
                    ->andReturnNull();
            });

            $this->postJson('/api/auth/send-code', [
                'countryCode' => '+86',
                'phone' => '13800138035',
                // 不传 type
            ])->assertStatus(200);

            // 验证验证码类型为 login
            $code = VerificationCode::where('phone', '13800138035')->first();
            expect($code->type)->toBe('login');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | POST /api/auth/logout - 退出登录
    |--------------------------------------------------------------------------
    */
    describe('POST /api/auth/logout', function () {

        it('登出成功', function () {
            $member = Member::factory()->create();
            $token = $member->createToken('api-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/auth/logout');

            $response->assertStatus(200)
                ->assertJson(['message' => '已退出登录']);

            // 验证 Token 已删除
            expect($member->fresh()->tokens()->count())->toBe(0);
        });

        it('登出后 Token 失效', function () {
            $member = Member::factory()->create();
            $token = $member->createToken('api-token')->plainTextToken;

            // 登出
            $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/auth/logout')
                ->assertStatus(200);

            // 验证 Token 已从数据库删除
            expect($member->fresh()->tokens()->count())->toBe(0);
        });

        it('未认证用户无法登出', function () {
            $response = $this->postJson('/api/auth/logout');

            $response->assertStatus(401);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | GET /api/auth/me - 获取当前用户信息
    |--------------------------------------------------------------------------
    */
    describe('GET /api/auth/me', function () {

        it('获取当前用户信息成功', function () {
            $member = Member::factory()->create([
                'nickname' => '测试用户',
                'country_code' => '+86',
                'phone' => '13800138040',
                'plan' => 'pro',
            ]);
            $token = $member->createToken('api-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/auth/me');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'nickname',
                        'country_code',
                        'phone',
                        'avatar',
                        'plan',
                        'subscription_expiry',
                        'phone_verified_at',
                        'status',
                        'created_at',
                    ],
                ])
                ->assertJsonPath('user.id', $member->id)
                ->assertJsonPath('user.nickname', '测试用户')
                ->assertJsonPath('user.plan', 'pro')
                ->assertJsonPath('user.phone', '13800138040');
        });

        it('未认证用户无法获取信息', function () {
            $response = $this->getJson('/api/auth/me');

            $response->assertStatus(401);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | GET /api/subscription/check - 检查订阅状态
    |--------------------------------------------------------------------------
    */
    describe('GET /api/subscription/check', function () {

        it('免费用户获取订阅状态', function () {
            $member = Member::factory()->create([
                'plan' => 'free',
                'subscription_expiry' => null,
            ]);
            $token = $member->createToken('api-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/subscription/check');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'active',
                    'plan',
                    'expiry_date',
                    'days_remaining',
                    'features' => [
                        'daily_print_limit',
                        'filters',
                        'custom_template',
                        'api_access',
                    ],
                ])
                ->assertJsonPath('plan', 'free')
                ->assertJsonPath('active', true)
                ->assertJsonPath('features.daily_print_limit', 10)
                ->assertJsonPath('features.filters', false);
        });

        it('Pro 用户获取订阅状态', function () {
            $member = Member::factory()->pro()->create();
            $token = $member->createToken('api-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/subscription/check');

            $response->assertStatus(200)
                ->assertJsonPath('plan', 'pro')
                ->assertJsonPath('active', true)
                ->assertJsonPath('features.daily_print_limit', 100)
                ->assertJsonPath('features.filters', true)
                ->assertJsonPath('features.custom_template', true);
        });

        it('Enterprise 用户获取订阅状态', function () {
            $member = Member::factory()->enterprise()->create();
            $token = $member->createToken('api-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/subscription/check');

            $response->assertStatus(200)
                ->assertJsonPath('plan', 'enterprise')
                ->assertJsonPath('active', true)
                ->assertJsonPath('features.daily_print_limit', -1) // 无限制
                ->assertJsonPath('features.api_access', true);
        });

        it('过期用户订阅状态为非活跃', function () {
            $member = Member::factory()->expired()->create();
            $token = $member->createToken('api-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/subscription/check');

            $response->assertStatus(200)
                ->assertJsonPath('active', false)
                ->assertJsonPath('days_remaining', 0);
        });

        it('未认证用户无法检查订阅', function () {
            $response = $this->getJson('/api/subscription/check');

            $response->assertStatus(401);
        });
    });
});
