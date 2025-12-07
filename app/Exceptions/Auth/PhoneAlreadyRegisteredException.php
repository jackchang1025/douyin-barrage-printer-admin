<?php

namespace App\Exceptions\Auth;

/**
 * 手机号已注册异常
 */
class PhoneAlreadyRegisteredException extends AuthException
{
    protected int $httpCode = 400;
    protected string $errorCode = 'PHONE_ALREADY_REGISTERED';

    public function __construct(string $message = '该手机号码已注册', ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}

