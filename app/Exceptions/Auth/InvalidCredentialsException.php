<?php

namespace App\Exceptions\Auth;

/**
 * 凭据错误异常（手机号或密码错误）
 */
class InvalidCredentialsException extends AuthException
{
    protected int $httpCode = 401;
    protected string $errorCode = 'INVALID_CREDENTIALS';

    public function __construct(string $message = '手机号或密码错误', ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}

