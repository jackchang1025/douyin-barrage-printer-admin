<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // 网站基本设置
            [
                'key' => 'site_name',
                'value' => '抖音弹幕打印系统',
                'type' => Setting::TYPE_STRING,
                'group' => Setting::GROUP_GENERAL,
                'label' => '网站名称',
                'description' => '网站名称，显示在首页标题和页脚',
                'sort_order' => 1,
            ],
            [
                'key' => 'site_slogan',
                'value' => '专业的直播弹幕打印解决方案',
                'type' => Setting::TYPE_STRING,
                'group' => Setting::GROUP_GENERAL,
                'label' => '网站标语',
                'description' => '网站标语，显示在首页副标题',
                'sort_order' => 2,
            ],
            [
                'key' => 'site_description',
                'value' => '实时捕获抖音直播间弹幕，支持自定义打印模板，让您的直播互动更加精彩',
                'type' => Setting::TYPE_TEXT,
                'group' => Setting::GROUP_GENERAL,
                'label' => '网站描述',
                'description' => '网站描述文字，显示在首页介绍区域',
                'sort_order' => 3,
            ],
            // 联系方式设置
            [
                'key' => 'contact_phone',
                'value' => '',
                'type' => Setting::TYPE_STRING,
                'group' => Setting::GROUP_CONTACT,
                'label' => '联系电话',
                'description' => '客服联系电话，用于用户续费咨询',
                'sort_order' => 1,
            ],
            [
                'key' => 'contact_wechat_qrcode',
                'value' => '',
                'type' => Setting::TYPE_IMAGE,
                'group' => Setting::GROUP_CONTACT,
                'label' => '微信二维码',
                'description' => '微信客服二维码图片',
                'sort_order' => 2,
            ],
            [
                'key' => 'contact_qq_qrcode',
                'value' => '',
                'type' => Setting::TYPE_IMAGE,
                'group' => Setting::GROUP_CONTACT,
                'label' => 'QQ二维码',
                'description' => 'QQ客服二维码图片',
                'sort_order' => 3,
            ],
            [
                'key' => 'contact_description',
                'value' => '如需续费，请联系客服',
                'type' => Setting::TYPE_TEXT,
                'group' => Setting::GROUP_CONTACT,
                'label' => '联系说明',
                'description' => '显示在订阅过期页面的说明文字',
                'sort_order' => 4,
            ],
            [
                'key' => 'renewal_guide',
                'value' => '扫描下方二维码或拨打客服电话，提供您的账号信息即可完成续费',
                'type' => Setting::TYPE_TEXT,
                'group' => Setting::GROUP_CONTACT,
                'label' => '续费指引',
                'description' => '续费操作指引说明',
                'sort_order' => 5,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
