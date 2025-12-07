<?php

namespace App\Exceptions\Sms;

use App\Exceptions\BusinessException;

/**
 * 短信服务异常基类
 */
class SmsException extends BusinessException
{
    protected int $httpCode = 500;
    protected string $errorCode = 'SMS_ERROR';

    public function __construct(
        string $message = '短信服务异常',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * 短信异常需要记录日志
     */
    public function report(): bool
    {
        return true;
    }
}

