<?php

namespace App\Http\Controllers;

use App\Models\AppVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    /**
     * 下载最新版本客户端（重定向到 API 下载接口）
     */
    public function latest(Request $request): RedirectResponse
    {
        $platform = $request->query('platform', 'win');
        $latest = AppVersion::getLatest($platform);

        if (!$latest || !$latest->file_path) {
            // 如果没有可用版本，返回首页
            return redirect()->route('home')->with('error', '暂无可用版本');
        }

        // 重定向到 API 下载接口
        return redirect()->route('api.app.download', ['version' => $latest->version]);
    }

    /**
     * 获取版本信息（JSON）
     */
    public function info(): \Illuminate\Http\JsonResponse
    {
        $latest = AppVersion::getLatest('win');

        if (!$latest) {
            return response()->json([
                'success' => false,
                'message' => '暂无可用版本',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'version' => $latest->version,
                'platform' => $latest->platform,
                'file_name' => $latest->file_name,
                'file_size' => $latest->file_size,
                'file_size_formatted' => $latest->file_size_formatted,
                'release_notes' => $latest->release_notes,
                'published_at' => $latest->published_at?->toIso8601String(),
                'download_url' => route('download.latest'),
            ],
        ]);
    }
}
