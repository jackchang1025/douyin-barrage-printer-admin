<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Overtrue\EasySms\EasySms;

class EasySmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(EasySms::class, function ($app) {
            return new EasySms(config('easysms'));
        });

        // 注册别名，方便使用 app('easy-sms') 获取实例
        $this->app->alias(EasySms::class, 'easy-sms');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
