<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * 显示首页
     */
    public function index(): View
    {
        // 获取网站基本信息
        $siteName = Setting::get('site_name', '抖音弹幕打印系统');
        $siteSlogan = Setting::get('site_slogan', '专业的直播弹幕打印解决方案');
        $siteDescription = Setting::get('site_description', '实时捕获抖音直播间弹幕，支持自定义打印模板，让您的直播互动更加精彩');

        // 获取媒体设置
        $siteFavicon = $this->getImageUrl(Setting::get('site_favicon'));
        $siteLogo = $this->getImageUrl(Setting::get('site_logo'));
        $heroMedia = $this->getHeroMedia();
        $heroVideoUrl = Setting::get('hero_video_url');

        // 获取 SEO 设置
        $seoKeywords = Setting::get('seo_keywords', '抖音弹幕打印,直播弹幕,弹幕打印机,抖音直播工具');
        $seoAuthor = Setting::get('seo_author', $siteName);
        $ogImage = $this->getImageUrl(Setting::get('og_image'));

        // 获取价格计划
        $plans = Plan::active()->ordered()->get();

        // 获取联系方式
        $contactPhone = Setting::get('contact_phone');
        $contactWechatQrcode = $this->getImageUrl(Setting::get('contact_wechat_qrcode'));
        $contactQqQrcode = $this->getImageUrl(Setting::get('contact_qq_qrcode'));
        $contactDescription = Setting::get('contact_description', '如需续费或咨询，请联系客服');

        // 功能特性
        $features = [
            [
                'icon' => 'monitor',
                'title' => '实时弹幕捕获',
                'description' => '使用先进的CDP协议技术，实时捕获抖音直播间的弹幕、礼物、关注等消息',
            ],
            [
                'icon' => 'printer',
                'title' => '智能打印',
                'description' => '支持热敏打印机，一键打印弹幕，自动过滤重复内容，支持自定义打印模板',
            ],
            [
                'icon' => 'filter',
                'title' => '高级过滤',
                'description' => '多维度弹幕过滤，按用户等级、礼物价值、关键词等条件筛选打印内容',
            ],
            [
                'icon' => 'settings',
                'title' => '灵活配置',
                'description' => '丰富的系统设置，支持自定义打印样式、字体大小、打印速度等参数',
            ],
        ];

        return view('home', compact(
            'siteName',
            'siteSlogan',
            'siteDescription',
            'siteFavicon',
            'siteLogo',
            'heroMedia',
            'heroVideoUrl',
            'seoKeywords',
            'seoAuthor',
            'ogImage',
            'plans',
            'features',
            'contactPhone',
            'contactWechatQrcode',
            'contactQqQrcode',
            'contactDescription'
        ));
    }

    /**
     * 获取图片完整URL
     */
    protected function getImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * 获取首页展示媒体
     */
    protected function getHeroMedia(): array
    {
        $media = Setting::get('hero_media', []);
        
        if (empty($media)) {
            return [];
        }

        // 确保是数组
        if (is_string($media)) {
            $media = json_decode($media, true) ?? [];
        }

        // 转换为完整URL
        return array_map(function ($path) {
            return [
                'url' => $this->getImageUrl($path),
                'type' => $this->getMediaType($path),
            ];
        }, $media);
    }

    /**
     * 获取媒体类型
     */
    protected function getMediaType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        return match ($extension) {
            'gif' => 'gif',
            'mp4', 'webm', 'ogg' => 'video',
            default => 'image',
        };
    }
}
