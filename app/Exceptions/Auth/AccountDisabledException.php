<?php

namespace App\Exceptions\Auth;

/**
 * 账号被禁用异常
 */
class AccountDisabledException extends AuthException
{
    protected int $httpCode = 403;
    protected string $errorCode = 'ACCOUNT_DISABLED';

    public function __construct(string $message = '账号已被禁用，请联系客服', ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}

