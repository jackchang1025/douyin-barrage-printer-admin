<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppVersionController extends Controller
{
    /**
     * 分块大小限制：50MB
     */
    private const CHUNK_SIZE = 50 * 1024 * 1024;
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

    /**
     * 初始化分块上传
     * POST /api/app/upload/init
     * 
     * 用于大文件分块上传，绕过 Cloudflare 100MB 限制
     */
    public function initChunkedUpload(Request $request): JsonResponse
    {
        // 验证 API Token
        if (!$this->validateUploadToken($request)) {
            return response()->json([
                'success' => false,
                'message' => '无效的上传令牌',
            ], 401);
        }

        $request->validate([
            'file_name' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1',
            'version' => 'required|string|max:20',
            'platform' => 'required|string|in:win,mac,linux',
            'sha512' => 'required|string',
            'release_notes' => 'nullable|string',
        ]);

        $fileSize = $request->input('file_size');
        $totalChunks = (int) ceil($fileSize / self::CHUNK_SIZE);

        // 生成上传会话 ID
        $uploadId = Str::uuid()->toString();

        // 创建临时目录
        $tempDir = "app-releases/temp/{$uploadId}";
        Storage::disk('public')->makeDirectory($tempDir);

        // 存储上传会话信息到缓存（24小时过期）
        $sessionData = [
            'upload_id' => $uploadId,
            'file_name' => $request->input('file_name'),
            'file_size' => $fileSize,
            'version' => $request->input('version'),
            'platform' => $request->input('platform'),
            'sha512' => $request->input('sha512'),
            'release_notes' => $request->input('release_notes'),
            'total_chunks' => $totalChunks,
            'uploaded_chunks' => [],
            'temp_dir' => $tempDir,
            'created_at' => now()->toIso8601String(),
        ];

        Cache::put("chunked_upload:{$uploadId}", $sessionData, now()->addHours(24));

        Log::info('分块上传初始化', [
            'upload_id' => $uploadId,
            'file_name' => $request->input('file_name'),
            'file_size' => $fileSize,
            'total_chunks' => $totalChunks,
        ]);

        return response()->json([
            'success' => true,
            'message' => '上传会话已创建',
            'data' => [
                'upload_id' => $uploadId,
                'total_chunks' => $totalChunks,
                'chunk_size' => self::CHUNK_SIZE,
            ],
        ]);
    }

    /**
     * 上传文件分块
     * POST /api/app/upload/chunk
     */
    public function uploadChunk(Request $request): JsonResponse
    {
        // 验证 API Token
        if (!$this->validateUploadToken($request)) {
            return response()->json([
                'success' => false,
                'message' => '无效的上传令牌',
            ], 401);
        }

        $request->validate([
            'upload_id' => 'required|string|uuid',
            'chunk_index' => 'required|integer|min:0',
            'chunk' => 'required|file',
        ]);

        $uploadId = $request->input('upload_id');
        $chunkIndex = (int) $request->input('chunk_index');

        // 获取上传会话
        $sessionData = Cache::get("chunked_upload:{$uploadId}");
        if (!$sessionData) {
            return response()->json([
                'success' => false,
                'message' => '上传会话不存在或已过期',
            ], 404);
        }

        // 验证分块索引
        if ($chunkIndex >= $sessionData['total_chunks']) {
            return response()->json([
                'success' => false,
                'message' => '无效的分块索引',
            ], 400);
        }

        // 存储分块文件
        $chunk = $request->file('chunk');
        $chunkPath = "{$sessionData['temp_dir']}/chunk_{$chunkIndex}";
        $chunk->storeAs(dirname($chunkPath), basename($chunkPath), 'public');

        // 更新已上传分块列表
        $sessionData['uploaded_chunks'][$chunkIndex] = true;
        Cache::put("chunked_upload:{$uploadId}", $sessionData, now()->addHours(24));

        $uploadedCount = count($sessionData['uploaded_chunks']);

        Log::debug('分块上传', [
            'upload_id' => $uploadId,
            'chunk_index' => $chunkIndex,
            'uploaded' => $uploadedCount,
            'total' => $sessionData['total_chunks'],
        ]);

        return response()->json([
            'success' => true,
            'message' => "分块 {$chunkIndex} 上传成功",
            'data' => [
                'chunk_index' => $chunkIndex,
                'uploaded_chunks' => $uploadedCount,
                'total_chunks' => $sessionData['total_chunks'],
            ],
        ]);
    }

    /**
     * 完成分块上传，合并文件
     * POST /api/app/upload/complete
     */
    public function completeChunkedUpload(Request $request): JsonResponse
    {
        // 验证 API Token
        if (!$this->validateUploadToken($request)) {
            return response()->json([
                'success' => false,
                'message' => '无效的上传令牌',
            ], 401);
        }

        $request->validate([
            'upload_id' => 'required|string|uuid',
        ]);

        $uploadId = $request->input('upload_id');

        // 获取上传会话
        $sessionData = Cache::get("chunked_upload:{$uploadId}");
        if (!$sessionData) {
            return response()->json([
                'success' => false,
                'message' => '上传会话不存在或已过期',
            ], 404);
        }

        // 验证所有分块已上传
        $uploadedCount = count($sessionData['uploaded_chunks']);
        if ($uploadedCount < $sessionData['total_chunks']) {
            return response()->json([
                'success' => false,
                'message' => "分块未完全上传 ({$uploadedCount}/{$sessionData['total_chunks']})",
            ], 400);
        }

        // 合并分块文件
        $platform = $sessionData['platform'];
        $fileName = $sessionData['file_name'];
        $finalPath = "app-releases/{$platform}/{$fileName}";
        $finalFullPath = Storage::disk('public')->path($finalPath);

        // 确保目标目录存在
        $targetDir = dirname($finalFullPath);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // 打开目标文件
        $outputFile = fopen($finalFullPath, 'wb');
        if (!$outputFile) {
            return response()->json([
                'success' => false,
                'message' => '无法创建目标文件',
            ], 500);
        }

        try {
            // 按顺序合并所有分块
            for ($i = 0; $i < $sessionData['total_chunks']; $i++) {
                $chunkPath = Storage::disk('public')->path("{$sessionData['temp_dir']}/chunk_{$i}");
                if (!file_exists($chunkPath)) {
                    throw new \Exception("分块 {$i} 文件不存在");
                }

                $chunkContent = file_get_contents($chunkPath);
                fwrite($outputFile, $chunkContent);
                unset($chunkContent);
            }
        } catch (\Exception $e) {
            fclose($outputFile);
            @unlink($finalFullPath);

            return response()->json([
                'success' => false,
                'message' => '合并分块失败: ' . $e->getMessage(),
            ], 500);
        }

        fclose($outputFile);

        // 验证文件大小
        $actualSize = filesize($finalFullPath);
        if ($actualSize !== $sessionData['file_size']) {
            Log::warning('分块合并后文件大小不匹配', [
                'expected' => $sessionData['file_size'],
                'actual' => $actualSize,
            ]);
        }

        // 清理临时文件
        Storage::disk('public')->deleteDirectory($sessionData['temp_dir']);
        Cache::forget("chunked_upload:{$uploadId}");

        // 检查版本是否已存在
        $version = $sessionData['version'];
        $existing = AppVersion::where('version', $version)
            ->where('platform', $platform)
            ->first();

        if ($existing && $existing->file_path && $existing->file_path !== $finalPath) {
            // 删除旧文件
            if (Storage::disk('public')->exists($existing->file_path)) {
                Storage::disk('public')->delete($existing->file_path);
            }
        }

        // 创建或更新版本记录
        $appVersion = AppVersion::updateOrCreate(
            ['version' => $version, 'platform' => $platform],
            [
                'file_path' => $finalPath,
                'file_name' => $fileName,
                'file_size' => $actualSize,
                'sha512' => $sessionData['sha512'],
                'release_notes' => $sessionData['release_notes'],
                'is_mandatory' => false,
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        Log::info('分块上传完成', [
            'upload_id' => $uploadId,
            'version' => $version,
            'platform' => $platform,
            'file_name' => $fileName,
            'file_size' => $actualSize,
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

    /**
     * 验证上传令牌
     */
    private function validateUploadToken(Request $request): bool
    {
        $token = $request->header('X-Upload-Token');
        $expectedToken = config('app.upload_token');

        return $expectedToken && $token === $expectedToken;
    }
}
