<?php

namespace App\Exceptions\Auth;

use App\Exceptions\BusinessException;

/**
 * 认证异常基类
 */
class AuthException extends BusinessException
{
    protected int $httpCode = 401;
    protected string $errorCode = 'AUTH_ERROR';

    public function __construct(string $message = '认证失败', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
