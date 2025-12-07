<?php

namespace App\Exceptions\Sms;

/**
 * 短信网关不可用异常
 */
class SmsGatewayException extends SmsException
{
    protected int $httpCode = 503;
    protected string $errorCode = 'SMS_GATEWAY_UNAVAILABLE';

    /**
     * 网关错误详情
     */
    protected array $gatewayErrors = [];

    public function __construct(
        string $message = '短信服务暂时不可用，请稍后重试',
        array $gatewayErrors = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
        $this->gatewayErrors = $gatewayErrors;
    }

    /**
     * 获取网关错误详情
     */
    public function getGatewayErrors(): array
    {
        return $this->gatewayErrors;
    }
}

