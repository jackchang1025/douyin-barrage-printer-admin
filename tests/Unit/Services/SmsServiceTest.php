<?php

use App\Services\SmsService;
use App\Exceptions\Sms\SmsSendFailedException;
use App\Exceptions\Sms\SmsGatewayException;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Mockery\MockInterface;

beforeEach(function () {
    // 设置配置
    config([
        'easysms.templates' => [
            'login' => '100001',
            'register' => '100001',
            'reset' => '100003',
        ],
        'easysms.code_expire_minutes' => 5,
    ]);
});

describe('SmsService', function () {

    describe('sendVerificationCode', function () {

        it('发送验证码成功', function () {
            // Mock EasySms 并绑定到容器
            $mockEasySms = Mockery::mock(EasySms::class);
            $mockEasySms->shouldReceive('send')
                ->once()
                ->with('13800138000', Mockery::type('array'))
                ->andReturn([]);

            // 绑定 mock 到容器
            app()->instance('easy-sms', $mockEasySms);

            $service = new SmsService();
            $service->sendVerificationCode('+8613800138000', '123456', 'login');

            // 如果没有抛出异常，测试通过
            expect(true)->toBeTrue();
        });

        it('格式化手机号 - 移除 +86 前缀', function () {
            $service = new SmsService();

            $reflection = new ReflectionClass($service);
            $method = $reflection->getMethod('formatPhoneNumber');
            $method->setAccessible(true);

            expect($method->invoke($service, '+8613800138000'))->toBe('13800138000');
        });

        it('格式化手机号 - 移除 86 前缀', function () {
            $service = new SmsService();

            $reflection = new ReflectionClass($service);
            $method = $reflection->getMethod('formatPhoneNumber');
            $method->setAccessible(true);

            expect($method->invoke($service, '8613800138000'))->toBe('13800138000');
        });

        it('格式化手机号 - 保留纯数字手机号', function () {
            $service = new SmsService();

            $reflection = new ReflectionClass($service);
            $method = $reflection->getMethod('formatPhoneNumber');
            $method->setAccessible(true);

            expect($method->invoke($service, '13800138000'))->toBe('13800138000');
        });

        it('网关不可用时抛出 SmsGatewayException', function () {
            // Mock EasySms 抛出异常
            $mockEasySms = Mockery::mock(EasySms::class);
            $mockEasySms->shouldReceive('send')
                ->once()
                ->andThrow(new NoGatewayAvailableException([]));

            app()->instance('easy-sms', $mockEasySms);

            $service = new SmsService();

            expect(fn() => $service->sendVerificationCode('+8613800138000', '123456', 'login'))
                ->toThrow(SmsGatewayException::class);
        });

        it('发送失败时抛出 SmsSendFailedException', function () {
            // Mock EasySms 抛出一般异常
            $mockEasySms = Mockery::mock(EasySms::class);
            $mockEasySms->shouldReceive('send')
                ->once()
                ->andThrow(new \Exception('发送失败'));

            app()->instance('easy-sms', $mockEasySms);

            $service = new SmsService();

            expect(fn() => $service->sendVerificationCode('+8613800138000', '123456', 'login'))
                ->toThrow(SmsSendFailedException::class);
        });
    });

    describe('getTemplateId', function () {

        it('返回登录模板 ID', function () {
            $service = new SmsService();

            $reflection = new ReflectionClass($service);
            $method = $reflection->getMethod('getTemplateId');
            $method->setAccessible(true);

            expect($method->invoke($service, 'login'))->toBe('100001');
        });

        it('返回注册模板 ID', function () {
            $service = new SmsService();

            $reflection = new ReflectionClass($service);
            $method = $reflection->getMethod('getTemplateId');
            $method->setAccessible(true);

            expect($method->invoke($service, 'register'))->toBe('100001');
        });

        it('返回重置密码模板 ID', function () {
            $service = new SmsService();

            $reflection = new ReflectionClass($service);
            $method = $reflection->getMethod('getTemplateId');
            $method->setAccessible(true);

            expect($method->invoke($service, 'reset'))->toBe('100003');
        });

        it('未知类型返回登录模板 ID', function () {
            $service = new SmsService();

            $reflection = new ReflectionClass($service);
            $method = $reflection->getMethod('getTemplateId');
            $method->setAccessible(true);

            expect($method->invoke($service, 'unknown'))->toBe('100001');
        });
    });

    describe('maskPhone', function () {

        it('脱敏标准11位手机号', function () {
            $service = new SmsService();

            $reflection = new ReflectionClass($service);
            $method = $reflection->getMethod('maskPhone');
            $method->setAccessible(true);

            expect($method->invoke($service, '13800138000'))->toBe('138****8000');
        });

        it('脱敏带国家码的手机号', function () {
            $service = new SmsService();

            $reflection = new ReflectionClass($service);
            $method = $reflection->getMethod('maskPhone');
            $method->setAccessible(true);

            expect($method->invoke($service, '+8613800138000'))->toBe('861****8000');
        });

        it('短手机号返回 ****', function () {
            $service = new SmsService();

            $reflection = new ReflectionClass($service);
            $method = $reflection->getMethod('maskPhone');
            $method->setAccessible(true);

            expect($method->invoke($service, '123456'))->toBe('****');
        });
    });

    describe('isChinesePhone', function () {

        it('识别 +86 为国内手机号', function () {
            $service = new SmsService();

            expect($service->isChinesePhone('+86'))->toBeTrue();
        });

        it('识别 86 为国内手机号', function () {
            $service = new SmsService();

            expect($service->isChinesePhone('86'))->toBeTrue();
        });

        it('识别 +1 为非国内手机号', function () {
            $service = new SmsService();

            expect($service->isChinesePhone('+1'))->toBeFalse();
        });

        it('识别 +852 为非国内手机号', function () {
            $service = new SmsService();

            expect($service->isChinesePhone('+852'))->toBeFalse();
        });
    });

    describe('常量定义', function () {

        it('定义了所有短信类型常量', function () {
            expect(SmsService::TYPE_LOGIN)->toBe('login');
            expect(SmsService::TYPE_REGISTER)->toBe('register');
            expect(SmsService::TYPE_RESET)->toBe('reset');
            expect(SmsService::TYPE_CHANGE_PHONE)->toBe('change_phone');
            expect(SmsService::TYPE_BIND_PHONE)->toBe('bind_phone');
            expect(SmsService::TYPE_VERIFY_PHONE)->toBe('verify_phone');
        });
    });
});
