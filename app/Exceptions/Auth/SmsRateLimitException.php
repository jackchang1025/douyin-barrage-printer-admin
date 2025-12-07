<?php

namespace App\Exceptions\Auth;

/**
 * 短信发送频率限制异常
 */
class SmsRateLimitException extends AuthException
{
    protected int $httpCode = 429;
    protected string $errorCode = 'SMS_RATE_LIMITED';

    public function __construct(string $message = '请稍后再试', ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}

