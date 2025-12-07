<?php

namespace App\Exceptions\Sms;

/**
 * 短信发送失败异常
 */
class SmsSendFailedException extends SmsException
{
    protected int $httpCode = 500;
    protected string $errorCode = 'SMS_SEND_FAILED';

    public function __construct(
        string $message = '短信发送失败，请稍后重试',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }
}

