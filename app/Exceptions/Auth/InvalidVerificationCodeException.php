<?php

namespace App\Exceptions\Auth;

/**
 * 验证码错误异常
 */
class InvalidVerificationCodeException extends AuthException
{
    protected int $httpCode = 400;
    protected string $errorCode = 'INVALID_VERIFICATION_CODE';

    public function __construct(string $message = '验证码不正确或已过期', ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}

