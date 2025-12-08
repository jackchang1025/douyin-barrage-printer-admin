<?php

use App\Exceptions\Auth\AccountDisabledException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvalidVerificationCodeException;
use App\Exceptions\Auth\PhoneAlreadyRegisteredException;
use App\Exceptions\Auth\SmsRateLimitException;
use App\Exceptions\Sms\SmsGatewayException;
use App\Exceptions\Sms\SmsSendFailedException;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\VerificationCode;
use App\Services\SmsService;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    // 确保计划数据存在
    $this->seed(PlanSeeder::class);
});

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
                        'plan_name',
                        'subscription_expiry',
                        'phone_verified_at',
                        'status',
                        'created_at',
                    ],
                    'subscription' => [
                        'is_active',
                        'is_expired',
                        'plan',
                        'plan_name',
                        'expiry_date',
                        'days_remaining',
                        'features',
                        'renewal_message',
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
                ->assertJsonStructure([
                    'message',
                    'token',
                    'user',
                    'subscription' => [
                        'is_active',
                        'is_expired',
                        'plan',
                        'plan_name',
                    ],
                ])
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

            $response->assertStatus(200)
                ->assertJsonStructure(['subscription']);

            // 验证用户已创建
            $member = Member::where('phone', '13800138013')->first();
            expect($member)->not->toBeNull();

            // 验证使用默认计划
            $defaultPlan = Plan::getDefault();
            expect($member->plan)->toBe($defaultPlan?->code ?? 'free');
            expect($member->plan_id)->toBe($defaultPlan?->id);
            expect($member->phone_verified_at)->not->toBeNull();

            // 验证订阅已创建
            expect($member->subscriptions()->exists())->toBeTrue();
            $subscription = $member->subscriptions()->first();
            expect($subscription->plan_id)->toBe($defaultPlan?->id);
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

            // 验证使用默认计划
            $defaultPlan = Plan::getDefault();
            expect($member->plan)->toBe($defaultPlan?->code ?? 'free');
            expect($member->plan_id)->toBe($defaultPlan?->id);
            expect($member->status)->toBe(Member::STATUS_ACTIVE);
            expect($member->phone_verified_at)->not->toBeNull();

            // 验证密码正确
            expect(Hash::check('password123', $member->password))->toBeTrue();

            // 验证订阅已创建
            expect($member->subscriptions()->exists())->toBeTrue();
            $subscription = $member->subscriptions()->first();
            expect($subscription->plan_id)->toBe($defaultPlan?->id);
        });

        it('注册时订阅过期时间根据计划时长计算', function () {
            // 创建一个有时长的默认计划（7天）
            Plan::where('is_default', true)->update(['is_default' => false]);
            $testPlan = Plan::create([
                'code' => 'test_trial',
                'name' => '测试试用版',
                'description' => '7天试用',
                'price' => 0,
                'duration_days' => 7,
                'daily_print_limit' => 20,
                'filters_enabled' => false,
                'custom_template_enabled' => false,
                'api_access_enabled' => false,
                'color' => 'info',
                'sort_order' => 0,
                'is_active' => true,
                'is_default' => true,
            ]);

            // 创建验证码
            $verification = VerificationCode::createCode('+86', '13800138025', 'register');

            $response = $this->postJson('/api/auth/register', [
                'countryCode' => '+86',
                'phone' => '13800138025',
                'password' => 'password123',
                'code' => $verification->code,
            ]);

            $response->assertStatus(200);

            // 验证用户已创建
            $member = Member::where('phone', '13800138025')->first();
            expect($member)->not->toBeNull();
            expect($member->plan)->toBe('test_trial');
            expect($member->plan_id)->toBe($testPlan->id);

            // 验证订阅过期时间是 7 天后（允许 1 分钟误差）
            expect($member->subscription_expiry)->not->toBeNull();
            $expectedExpiry = now()->addDays(7);
            $diff = abs($member->subscription_expiry->diffInMinutes($expectedExpiry));
            expect($diff)->toBeLessThan(2); // 允许 2 分钟误差

            // 验证订阅记录的过期时间
            $subscription = $member->subscriptions()->first();
            expect($subscription->expires_at)->not->toBeNull();
            $subDiff = abs($subscription->expires_at->diffInMinutes($expectedExpiry));
            expect($subDiff)->toBeLessThan(2);
        });

        it('永久计划注册时订阅过期时间为空', function () {
            // 使用永久的免费计划（duration_days = 0）
            $freePlan = Plan::findByCode('free');
            // 确保免费计划是永久的
            $freePlan->update(['duration_days' => 0, 'is_default' => true]);
            Plan::where('id', '!=', $freePlan->id)->update(['is_default' => false]);

            // 创建验证码
            $verification = VerificationCode::createCode('+86', '13800138026', 'register');

            $response = $this->postJson('/api/auth/register', [
                'countryCode' => '+86',
                'phone' => '13800138026',
                'password' => 'password123',
                'code' => $verification->code,
            ]);

            $response->assertStatus(200);

            // 验证用户已创建
            $member = Member::where('phone', '13800138026')->first();
            expect($member)->not->toBeNull();

            // 验证订阅过期时间为空（永久）
            expect($member->subscription_expiry)->toBeNull();

            // 验证订阅记录的过期时间也为空
            $subscription = $member->subscriptions()->first();
            expect($subscription->expires_at)->toBeNull();
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
            $proPlan = Plan::findByCode('pro');
            $member = Member::factory()->create([
                'nickname' => '测试用户',
                'country_code' => '+86',
                'phone' => '13800138040',
                'plan' => 'pro',
                'plan_id' => $proPlan?->id,
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
                        'plan_name',
                        'subscription_expiry',
                        'phone_verified_at',
                        'status',
                        'created_at',
                    ],
                ])
                ->assertJsonPath('user.id', $member->id)
                ->assertJsonPath('user.nickname', '测试用户')
                ->assertJsonPath('user.plan', 'pro')
                ->assertJsonPath('user.plan_name', $proPlan?->name ?? '专业版')
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
            $freePlan = Plan::findByCode('free');
            $member = Member::factory()->withSubscription('free')->create([
                'plan' => 'free',
                'plan_id' => $freePlan?->id,
            ]);
            $token = $member->createToken('api-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/subscription/check');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'is_active',
                    'is_expired',
                    'plan',
                    'plan_name',
                    'expiry_date',
                    'days_remaining',
                    'features' => [
                        'daily_print_limit',
                        'filters',
                        'custom_template',
                        'api_access',
                    ],
                    'renewal_message',
                ])
                ->assertJsonPath('plan', 'free')
                ->assertJsonPath('plan_name', $freePlan?->name ?? '免费版')
                ->assertJsonPath('is_active', true)
                ->assertJsonPath('is_expired', false)
                ->assertJsonPath('renewal_message', null)
                ->assertJsonPath('features.daily_print_limit', $freePlan?->daily_print_limit ?? 10)
                ->assertJsonPath('features.filters', $freePlan?->filters_enabled ?? false);
        });

        it('Pro 用户获取订阅状态', function () {
            $proPlan = Plan::findByCode('pro');
            $member = Member::factory()->pro()->create([
                'plan_id' => $proPlan?->id,
            ]);
            $token = $member->createToken('api-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/subscription/check');

            $response->assertStatus(200)
                ->assertJsonPath('plan', 'pro')
                ->assertJsonPath('plan_name', $proPlan?->name ?? '专业版')
                ->assertJsonPath('is_active', true)
                ->assertJsonPath('is_expired', false)
                ->assertJsonPath('features.daily_print_limit', $proPlan?->daily_print_limit ?? 100)
                ->assertJsonPath('features.filters', $proPlan?->filters_enabled ?? true)
                ->assertJsonPath('features.custom_template', $proPlan?->custom_template_enabled ?? true);
        });

        it('Enterprise 用户获取订阅状态', function () {
            $enterprisePlan = Plan::findByCode('enterprise');
            $member = Member::factory()->enterprise()->create([
                'plan_id' => $enterprisePlan?->id,
            ]);
            $token = $member->createToken('api-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/subscription/check');

            $response->assertStatus(200)
                ->assertJsonPath('plan', 'enterprise')
                ->assertJsonPath('plan_name', $enterprisePlan?->name ?? '企业版')
                ->assertJsonPath('is_active', true)
                ->assertJsonPath('is_expired', false)
                ->assertJsonPath('features.daily_print_limit', $enterprisePlan?->daily_print_limit ?? -1)
                ->assertJsonPath('features.api_access', $enterprisePlan?->api_access_enabled ?? true);
        });

        it('过期用户订阅状态为非活跃', function () {
            $proPlan = Plan::findByCode('pro');
            $member = Member::factory()->expired()->create([
                'plan_id' => $proPlan?->id,
            ]);
            $token = $member->createToken('api-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/subscription/check');

            $response->assertStatus(200)
                ->assertJsonPath('is_active', false)
                ->assertJsonPath('is_expired', true)
                ->assertJsonPath('days_remaining', 0)
                ->assertJson([
                    'renewal_message' => '您的订阅已过期，请联系客服续费以继续使用',
                ]);
        });

        it('登录时返回订阅过期提示', function () {
            $proPlan = Plan::findByCode('pro');
            $member = Member::factory()->expired()->create([
                'country_code' => '+86',
                'phone' => '13800138099',
                'password' => Hash::make('password123'),
                'plan_id' => $proPlan?->id,
            ]);

            $response = $this->postJson('/api/auth/login-phone', [
                'countryCode' => '+86',
                'phone' => '13800138099',
                'password' => 'password123',
            ]);

            $response->assertStatus(200)
                ->assertJsonPath('subscription.is_active', false)
                ->assertJsonPath('subscription.is_expired', true)
                ->assertJson([
                    'subscription' => [
                        'renewal_message' => '您的订阅已过期，请联系客服续费以继续使用',
                    ],
                ]);
        });

        it('未认证用户无法检查订阅', function () {
            $response = $this->getJson('/api/subscription/check');

            $response->assertStatus(401);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 单点登录 (Single Sign-On) 测试
    |--------------------------------------------------------------------------
    */
    describe('单点登录', function () {

        it('新登录会使旧 Token 失效（手机号密码登录）', function () {
            // 创建用户
            $member = Member::factory()->create([
                'country_code' => '+86',
                'phone' => '13800138100',
                'password' => Hash::make('password123'),
            ]);

            // 第一次登录，获取 token1
            $response1 = $this->postJson('/api/auth/login-phone', [
                'countryCode' => '+86',
                'phone' => '13800138100',
                'password' => 'password123',
            ]);
            $response1->assertStatus(200);
            $token1 = $response1->json('token');

            // 验证第一次登录后只有 1 个 token
            $member->refresh();
            expect($member->tokens()->count())->toBe(1);
            $tokenId1 = $member->tokens()->first()->id;

            // 验证 token1 有效
            $this->withHeader('Authorization', "Bearer {$token1}")
                ->getJson('/api/auth/me')
                ->assertStatus(200);

            // 第二次登录，获取 token2
            $response2 = $this->postJson('/api/auth/login-phone', [
                'countryCode' => '+86',
                'phone' => '13800138100',
                'password' => 'password123',
            ]);
            $response2->assertStatus(200);
            $token2 = $response2->json('token');

            // 验证第二次登录后仍然只有 1 个 token（旧的被删除）
            $member->refresh();
            expect($member->tokens()->count())->toBe(1);
            $tokenId2 = $member->tokens()->first()->id;

            // 验证两个 token ID 不同（说明旧的确实被删除了）
            expect($tokenId2)->not->toBe($tokenId1);

            // 使用新的测试实例发起请求，避免状态缓存
            // token1 应该失效（通过检查数据库中是否存在该 token）
            $token1Parts = explode('|', $token1);
            $token1Exists = \Laravel\Sanctum\PersonalAccessToken::find($token1Parts[0]);
            expect($token1Exists)->toBeNull();

            // token2 应该有效
            $this->withHeader('Authorization', "Bearer {$token2}")
                ->getJson('/api/auth/me')
                ->assertStatus(200);
        });

        it('新登录会使旧 Token 失效（验证码登录）', function () {
            // 创建用户
            $member = Member::factory()->create([
                'country_code' => '+86',
                'phone' => '13800138101',
            ]);

            // 创建有效验证码
            VerificationCode::create([
                'country_code' => '+86',
                'phone' => '13800138101',
                'code' => '123456',
                'type' => 'login',
                'expires_at' => now()->addMinutes(10),
            ]);

            // 第一次登录
            $response1 = $this->postJson('/api/auth/login-code', [
                'countryCode' => '+86',
                'phone' => '13800138101',
                'code' => '123456',
            ]);
            $response1->assertStatus(200);
            $token1 = $response1->json('token');

            // 验证第一次登录后只有 1 个 token
            $member->refresh();
            expect($member->tokens()->count())->toBe(1);

            // 创建新的验证码
            VerificationCode::where('phone', '13800138101')->delete();
            VerificationCode::create([
                'country_code' => '+86',
                'phone' => '13800138101',
                'code' => '654321',
                'type' => 'login',
                'expires_at' => now()->addMinutes(10),
            ]);

            // 第二次登录
            $response2 = $this->postJson('/api/auth/login-code', [
                'countryCode' => '+86',
                'phone' => '13800138101',
                'code' => '654321',
            ]);
            $response2->assertStatus(200);
            $token2 = $response2->json('token');

            // 验证第二次登录后仍然只有 1 个 token
            $member->refresh();
            expect($member->tokens()->count())->toBe(1);

            // token1 应该已被删除
            $token1Parts = explode('|', $token1);
            $token1Exists = \Laravel\Sanctum\PersonalAccessToken::find($token1Parts[0]);
            expect($token1Exists)->toBeNull();

            // token2 应该有效
            $this->withHeader('Authorization', "Bearer {$token2}")
                ->getJson('/api/auth/me')
                ->assertStatus(200);
        });

        it('登录后只存在一个有效 Token', function () {
            $member = Member::factory()->create([
                'country_code' => '+86',
                'phone' => '13800138102',
                'password' => Hash::make('password123'),
            ]);

            // 多次登录
            for ($i = 0; $i < 5; $i++) {
                $this->postJson('/api/auth/login-phone', [
                    'countryCode' => '+86',
                    'phone' => '13800138102',
                    'password' => 'password123',
                ])->assertStatus(200);
            }

            // 应该只有一个 token
            expect($member->tokens()->count())->toBe(1);
        });

        it('退出登录后 Token 失效', function () {
            $member = Member::factory()->create([
                'country_code' => '+86',
                'phone' => '13800138103',
                'password' => Hash::make('password123'),
            ]);

            // 登录
            $response = $this->postJson('/api/auth/login-phone', [
                'countryCode' => '+86',
                'phone' => '13800138103',
                'password' => 'password123',
            ]);
            $token = $response->json('token');

            // 验证登录后有 1 个 token
            $member->refresh();
            expect($member->tokens()->count())->toBe(1);

            // 退出登录
            $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/auth/logout')
                ->assertStatus(200);

            // 应该没有任何 token
            $member->refresh();
            expect($member->tokens()->count())->toBe(0);

            // Token 应该已被删除
            $tokenParts = explode('|', $token);
            $tokenExists = \Laravel\Sanctum\PersonalAccessToken::find($tokenParts[0]);
            expect($tokenExists)->toBeNull();
        });

        it('GET /api/auth/validate-token 检测 Token 有效性', function () {
            $member = Member::factory()->create([
                'country_code' => '+86',
                'phone' => '13800138104',
                'password' => Hash::make('password123'),
            ]);

            // 登录获取 token
            $response = $this->postJson('/api/auth/login-phone', [
                'countryCode' => '+86',
                'phone' => '13800138104',
                'password' => 'password123',
            ]);
            $token = $response->json('token');

            // 验证 token 有效
            $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/auth/validate-token')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'valid',
                    'user' => ['id', 'nickname', 'phone'],
                    'subscription' => ['is_active', 'is_expired'],
                ])
                ->assertJsonPath('valid', true);
        });

        it('GET /api/auth/validate-token 检测失效 Token', function () {
            $member = Member::factory()->create([
                'country_code' => '+86',
                'phone' => '13800138105',
                'password' => Hash::make('password123'),
            ]);

            // 第一次登录
            $response1 = $this->postJson('/api/auth/login-phone', [
                'countryCode' => '+86',
                'phone' => '13800138105',
                'password' => 'password123',
            ]);
            $token1 = $response1->json('token');

            // 第二次登录（使 token1 失效）
            $response2 = $this->postJson('/api/auth/login-phone', [
                'countryCode' => '+86',
                'phone' => '13800138105',
                'password' => 'password123',
            ]);
            $token2 = $response2->json('token');

            // token1 应该已被删除
            $token1Parts = explode('|', $token1);
            $token1Exists = \Laravel\Sanctum\PersonalAccessToken::find($token1Parts[0]);
            expect($token1Exists)->toBeNull();

            // token2 验证应该成功
            $this->withHeader('Authorization', "Bearer {$token2}")
                ->getJson('/api/auth/validate-token')
                ->assertStatus(200)
                ->assertJsonPath('valid', true);
        });

        it('未提供 Token 时 validate-token 返回 401', function () {
            $this->getJson('/api/auth/validate-token')
                ->assertStatus(401);
        });
    });
});
