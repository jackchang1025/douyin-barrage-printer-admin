<?php

namespace App\Services;

use App\Exceptions\Sms\SmsGatewayException;
use App\Exceptions\Sms\SmsSendFailedException;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * 短信类型常量
     */
    const TYPE_LOGIN = 'login';
    const TYPE_REGISTER = 'register';
    const TYPE_RESET = 'reset';
    const TYPE_CHANGE_PHONE = 'change_phone';
    const TYPE_BIND_PHONE = 'bind_phone';
    const TYPE_VERIFY_PHONE = 'verify_phone';

    /**
     * 获取 EasySms 实例
     * 使用 Laravel 容器获取由 EasySmsServiceProvider 注册的实例
     *
     * @return EasySms
     */
    protected function getEasySms(): EasySms
    {
        return app('easy-sms');
    }

    /**
     * 发送验证码短信
     *
     * @param string $phone 手机号码（含国家码，如 +8613800138000）
     * @param string $code 验证码
     * @param string $type 短信类型
     * @throws SmsSendFailedException 发送失败时抛出
     * @throws SmsGatewayException 网关不可用时抛出
     */
    public function sendVerificationCode(string $phone, string $code, string $type = self::TYPE_LOGIN): void
    {
        // 处理手机号格式（移除 + 号，阿里云不需要）
        $phoneNumber = $this->formatPhoneNumber($phone);

        // 获取模板 ID
        $templateId = $this->getTemplateId($type);

        // 验证码有效期（分钟）
        $expireMinutes = config('easysms.code_expire_minutes', 5);

        try {
            $this->getEasySms()->send($phoneNumber, [
                'template' => $templateId,
                'data' => [
                    'code' => $code,
                    'min' => $expireMinutes,
                ],
            ]);

            Log::info('短信发送成功', [
                'phone' => $this->maskPhone($phone),
                'type' => $type,
                'template' => $templateId,
            ]);
        } catch (NoGatewayAvailableException $e) {
            // 获取所有网关的错误信息
            $gatewayErrors = [];
            foreach ($e->getExceptions() as $gateway => $exception) {
                $gatewayErrors[$gateway] = $exception->getMessage();
            }

            Log::error('短信发送失败 - 所有网关不可用', [
                'phone' => $this->maskPhone($phone),
                'type' => $type,
                'errors' => $gatewayErrors,
            ]);

            throw new SmsGatewayException(
                '短信服务暂时不可用，请稍后重试',
                $gatewayErrors,
                $e
            );
        } catch (\Exception $e) {
            Log::error('短信发送异常', [
                'phone' => $this->maskPhone($phone),
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            throw new SmsSendFailedException(
                '短信发送失败，请稍后重试',
                $e
            );
        }
    }

    /**
     * 格式化手机号码
     * 阿里云短信只需要纯数字手机号，不需要国家码前缀
     *
     * @param string $phone 手机号（可能包含 +86 等国家码）
     * @return string 格式化后的手机号
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // 移除所有非数字字符
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // 如果以 86 开头且长度为 13 位，移除 86
        if (str_starts_with($phone, '86') && strlen($phone) === 13) {
            $phone = substr($phone, 2);
        }

        return $phone;
    }

    /**
     * 根据类型获取模板 ID
     *
     * @param string $type
     * @return string
     */
    protected function getTemplateId(string $type): string
    {
        $templates = config('easysms.templates', []);

        return $templates[$type] ?? $templates['login'] ?? '100001';
    }

    /**
     * 脱敏手机号（用于日志记录）
     *
     * @param string $phone
     * @return string
     */
    protected function maskPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) >= 7) {
            return substr($phone, 0, 3) . '****' . substr($phone, -4);
        }

        return '****';
    }

    /**
     * 检查手机号是否为国内手机号
     *
     * @param string $countryCode
     * @return bool
     */
    public function isChinesePhone(string $countryCode): bool
    {
        return in_array($countryCode, ['+86', '86']);
    }
}
