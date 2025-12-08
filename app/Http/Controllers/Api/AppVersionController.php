<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppVersionController extends Controller
{
    /**
     * 获取 latest.yml（electron-updater 兼容）
     * GET /api/app/latest.yml
     * 
     * electron-updater 会将 setFeedURL 的基础 URL 与文件名拼接
     * 所以这里的 url 和 path 只需要文件名
     */
    public function latestYml(Request $request): Response
    {
        $platform = $request->query('platform', 'win');
        $latest = AppVersion::getLatest($platform);

        if (!$latest || !$latest->file_path) {
            return response('version: 0.0.0', 200, [
                'Content-Type' => 'text/yaml; charset=utf-8',
            ]);
        }

        // electron-updater 需要的 YAML 格式
        // url 和 path 只需要文件名，electron-updater 会自动拼接基础 URL
        $fileName = $latest->file_name;

        $yaml = "version: {$latest->version}\n";
        $yaml .= "files:\n";
        $yaml .= "  - url: {$fileName}\n";
        $yaml .= "    sha512: {$latest->sha512}\n";
        $yaml .= "    size: {$latest->file_size}\n";
        $yaml .= "path: {$fileName}\n";
        $yaml .= "sha512: {$latest->sha512}\n";
        $yaml .= "releaseDate: " . ($latest->published_at?->toIso8601String() ?? now()->toIso8601String()) . "\n";

        return response($yaml, 200, [
            'Content-Type' => 'text/yaml; charset=utf-8',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * 获取最新版本信息（JSON）
     * GET /api/app/latest
     */
    public function latest(Request $request): JsonResponse
    {
        $platform = $request->query('platform', 'win');
        $latest = AppVersion::getLatest($platform);

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
                'sha512' => $latest->sha512,
                'release_notes' => $latest->release_notes,
                'is_mandatory' => $latest->is_mandatory,
                'published_at' => $latest->published_at?->toIso8601String(),
                'download_url' => url("/api/app/download/{$latest->version}"),
            ],
        ]);
    }

    /**
     * 下载安装包（按版本号）
     * GET /api/app/download/{version}
     */
    public function download(Request $request, string $version): BinaryFileResponse|JsonResponse
    {
        $platform = $request->query('platform', 'win');

        $appVersion = AppVersion::where('version', $version)
            ->where('platform', $platform)
            ->where('is_published', true)
            ->first();

        if (!$appVersion || !$appVersion->file_path) {
            return response()->json([
                'success' => false,
                'message' => '版本不存在或文件不可用',
            ], 404);
        }

        return $this->streamDownload($appVersion);
    }

    /**
     * 下载安装包（按文件名，electron-updater 使用）
     * GET /api/app/{fileName}
     */
    public function downloadByFileName(string $fileName): BinaryFileResponse|JsonResponse
    {
        $appVersion = AppVersion::where('file_name', $fileName)
            ->where('is_published', true)
            ->first();

        if (!$appVersion || !$appVersion->file_path) {
            return response()->json([
                'success' => false,
                'message' => '文件不存在',
            ], 404);
        }

        return $this->streamDownload($appVersion);
    }

    /**
     * 执行文件下载
     */
    private function streamDownload(AppVersion $appVersion): BinaryFileResponse|JsonResponse
    {
        $filePath = Storage::disk('public')->path($appVersion->file_path);

        if (!file_exists($filePath)) {
            Log::error('安装包文件不存在', ['path' => $filePath]);
            return response()->json([
                'success' => false,
                'message' => '文件不存在',
            ], 404);
        }

        // 增加下载计数
        $appVersion->incrementDownloadCount();

        return response()->download($filePath, $appVersion->file_name, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * 上传新版本（需要 API Token 认证）
     * POST /api/app/upload
     */
    public function upload(Request $request): JsonResponse
    {
        // 验证 API Token
        $token = $request->header('X-Upload-Token');
        $expectedToken = config('app.upload_token');

        if (!$expectedToken || $token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => '无效的上传令牌',
            ], 401);
        }

        $request->validate([
            'version' => 'required|string|max:20',
            'platform' => 'required|string|in:win,mac,linux',
            'file' => 'required|file|max:512000', // 最大 500MB
            'sha512' => 'required|string',
            'release_notes' => 'nullable|string',
            'is_mandatory' => 'nullable|boolean',
        ]);

        $version = $request->input('version');
        $platform = $request->input('platform');

        // 检查版本是否已存在
        $existing = AppVersion::where('version', $version)
            ->where('platform', $platform)
            ->first();

        if ($existing) {
            // 删除旧文件
            if ($existing->file_path && Storage::disk('public')->exists($existing->file_path)) {
                Storage::disk('public')->delete($existing->file_path);
            }
        }

        // 存储文件
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $filePath = $file->storeAs("app-releases/{$platform}", $fileName, 'public');

        // 创建或更新版本记录
        $appVersion = AppVersion::updateOrCreate(
            ['version' => $version, 'platform' => $platform],
            [
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => $file->getSize(),
                'sha512' => $request->input('sha512'),
                'release_notes' => $request->input('release_notes'),
                'is_mandatory' => $request->boolean('is_mandatory', false),
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        Log::info('新版本上传成功', [
            'version' => $version,
            'platform' => $platform,
            'file_name' => $fileName,
        ]);

        return response()->json([
            'success' => true,
            'message' => '版本上传成功',
            'data' => [
                'version' => $appVersion->version,
                'platform' => $appVersion->platform,
                'file_name' => $appVersion->file_name,
                'file_size' => $appVersion->file_size,
                'download_url' => url("/api/app/download/{$appVersion->version}"),
            ],
        ]);
    }
}
