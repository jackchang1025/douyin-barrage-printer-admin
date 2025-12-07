<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 默认发送配置
    |--------------------------------------------------------------------------
    |
    | strategy: 发送策略，默认使用 order 策略（按顺序尝试网关）
    | - \Overtrue\EasySms\Strategies\OrderStrategy::class: 按顺序依次尝试
    | - \Overtrue\EasySms\Strategies\RandomStrategy::class: 随机选择网关
    |
    | gateways: 可用网关列表
    |
    */
    'default' => [
        'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,
        'gateways' => [
            'aliyundypns',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 超时设置
    |--------------------------------------------------------------------------
    */
    'timeout' => 5.0,

    /*
    |--------------------------------------------------------------------------
    | 网关配置
    |--------------------------------------------------------------------------
    */
    'gateways' => [
        // 错误日志（用于调试）
        'errorlog' => [
            'file' => storage_path('logs/easy-sms.log'),
        ],

        // 阿里云短信认证（号码认证服务 DYPNS）
        // 文档: https://dypns.console.aliyun.com/smsCertParamsConfig
        'aliyundypns' => [
            'access_key_id' => env('ALIYUN_SMS_ACCESS_KEY_ID'),
            'access_key_secret' => env('ALIYUN_SMS_ACCESS_KEY_SECRET'),
            'sign_name' => env('ALIYUN_SMS_SIGN_NAME'),
        ],

        // 阿里云标准短信服务（备用）
        'aliyun' => [
            'access_key_id' => env('ALIYUN_SMS_ACCESS_KEY_ID'),
            'access_key_secret' => env('ALIYUN_SMS_ACCESS_KEY_SECRET'),
            'sign_name' => env('ALIYUN_SMS_SIGN_NAME'),
        ],

        // 腾讯云短信（备用）
        'qcloud' => [
            'sdk_app_id' => env('QCLOUD_SMS_SDK_APP_ID'),
            'secret_id' => env('QCLOUD_SMS_SECRET_ID'),
            'secret_key' => env('QCLOUD_SMS_SECRET_KEY'),
            'sign_name' => env('QCLOUD_SMS_SIGN_NAME'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 短信模板配置
    |--------------------------------------------------------------------------
    |
    | 根据阿里云配置的短信模板：
    | - 模板变量: ${code} 验证码, ${min} 有效分钟数
    | - 阿里云 DYPNS 模板 ID 格式: 100001, 100002 等（纯数字）
    | - 阿里云标准短信模板 ID 格式: SMS_123456789
    |
    */
    'templates' => [
        // 登录/注册验证码
        'login' => env('ALIYUN_SMS_TEMPLATE_LOGIN', '100001'),
        'register' => env('ALIYUN_SMS_TEMPLATE_REGISTER', '100001'),

        // 修改绑定手机号
        'change_phone' => env('ALIYUN_SMS_TEMPLATE_CHANGE_PHONE', '100002'),

        // 重置密码
        'reset' => env('ALIYUN_SMS_TEMPLATE_RESET', '100003'),

        // 绑定新手机号
        'bind_phone' => env('ALIYUN_SMS_TEMPLATE_BIND_PHONE', '100004'),

        // 验证绑定手机号
        'verify_phone' => env('ALIYUN_SMS_TEMPLATE_VERIFY_PHONE', '100005'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 验证码有效期（分钟）
    |--------------------------------------------------------------------------
    */
    'code_expire_minutes' => 5,
];
