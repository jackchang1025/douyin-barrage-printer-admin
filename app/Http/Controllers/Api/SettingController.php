<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * 设置控制器
 *
 * 提供公开的设置获取接口
 */
class SettingController extends Controller
{
    /**
     * 获取联系方式设置
     *
     * GET /api/settings/contact
     *
     * @return JsonResponse
     */
    public function getContactSettings(): JsonResponse
    {
        $settings = Setting::getContactSettings();

        // 处理图片 URL
        $wechatQrcode = $settings['contact_wechat_qrcode'];
        $qqQrcode = $settings['contact_qq_qrcode'];

        return response()->json([
            'contact_phone' => $settings['contact_phone'],
            'contact_wechat_qrcode' => $wechatQrcode ? $this->getImageUrl($wechatQrcode) : null,
            'contact_qq_qrcode' => $qqQrcode ? $this->getImageUrl($qqQrcode) : null,
            'contact_description' => $settings['contact_description'],
            'renewal_guide' => $settings['renewal_guide'],
        ]);
    }

    /**
     * 获取图片完整 URL
     *
     * @param string $path
     * @return string
     */
    protected function getImageUrl(string $path): string
    {
        // 如果已经是完整 URL，直接返回
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // 返回公共存储的 URL
        return Storage::disk('public')->url($path);
    }
}
