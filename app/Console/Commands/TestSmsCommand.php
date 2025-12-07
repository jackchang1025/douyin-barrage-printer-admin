<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class TestSmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:test 
                            {phone : 手机号码（如 13800138000）}
                            {--code= : 验证码（默认随机生成6位）}
                            {--type=login : 短信类型（login/register/reset/change_phone/bind_phone/verify_phone）}
                            {--country=+86 : 国家区号}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试短信验证码发送功能';

    /**
     * Execute the console command.
     */
    public function handle(SmsService $smsService): int
    {
        $phone = $this->argument('phone');
        $countryCode = $this->option('country');
        $code = $this->option('code') ?? $this->generateCode();
        $type = $this->option('type');

        $fullPhone = $countryCode . $phone;

        $this->info('========================================');
        $this->info('       短信发送测试');
        $this->info('========================================');
        $this->newLine();

        $this->table(
            ['参数', '值'],
            [
                ['手机号', $fullPhone],
                ['验证码', $code],
                ['短信类型', $type],
                ['模板', config("easysms.templates.{$type}", '未配置')],
            ]
        );

        $this->newLine();

        if (!$this->confirm('确认发送短信？')) {
            $this->warn('已取消发送');
            return self::SUCCESS;
        }

        $this->info('正在发送短信...');
        $this->newLine();

        try {
            $smsService->sendVerificationCode($fullPhone, $code, $type);

            $this->newLine();
            $this->info('✅ 短信发送成功！');
            $this->newLine();
            $this->table(
                ['项目', '内容'],
                [
                    ['手机号', $fullPhone],
                    ['验证码', $code],
                    ['有效期', config('easysms.code_expire_minutes', 5) . ' 分钟'],
                ]
            );

            return self::SUCCESS;
        } catch (\App\Exceptions\Sms\SmsGatewayException $e) {
            $this->newLine();
            $this->error('❌ 短信网关不可用');
            $this->error('错误信息: ' . $e->getMessage());

            $gatewayErrors = $e->getGatewayErrors();
            if (!empty($gatewayErrors)) {
                $this->newLine();
                $this->warn('网关错误详情:');
                foreach ($gatewayErrors as $gateway => $error) {
                    $this->line("  [{$gateway}] {$error}");
                }
            }

            return self::FAILURE;
        } catch (\App\Exceptions\Sms\SmsSendFailedException $e) {
            $this->newLine();
            $this->error('❌ 短信发送失败');
            $this->error('错误信息: ' . $e->getMessage());

            if ($e->getPrevious()) {
                $this->line('原始错误: ' . $e->getPrevious()->getMessage());
            }

            return self::FAILURE;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ 发生未知错误');
            $this->error('错误信息: ' . $e->getMessage());
            $this->line('错误类型: ' . get_class($e));

            return self::FAILURE;
        }
    }

    /**
     * 生成随机验证码
     */
    protected function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
