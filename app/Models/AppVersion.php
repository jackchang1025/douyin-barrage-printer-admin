<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AppVersion extends Model
{
    protected $fillable = [
        'version',
        'platform',
        'file_path',
        'file_name',
        'file_size',
        'sha512',
        'release_notes',
        'is_mandatory',
        'is_published',
        'published_at',
        'download_count',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_mandatory' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'download_count' => 'integer',
    ];

    /**
     * 获取最新发布版本
     */
    public static function getLatest(string $platform = 'win'): ?self
    {
        return static::where('platform', $platform)
            ->where('is_published', true)
            ->orderByRaw("CAST(SUBSTRING_INDEX(version, '.', 1) AS UNSIGNED) DESC")
            ->orderByRaw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(version, '.', 2), '.', -1) AS UNSIGNED) DESC")
            ->orderByRaw("CAST(SUBSTRING_INDEX(version, '.', -1) AS UNSIGNED) DESC")
            ->first();
    }

    /**
     * 获取下载 URL
     */
    public function getDownloadUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * 格式化文件大小
     */
    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * 版本号比较（返回 1 表示 $this 更新，-1 表示更旧，0 表示相同）
     */
    public function compareVersion(string $otherVersion): int
    {
        return version_compare($this->version, $otherVersion);
    }

    /**
     * 增加下载计数
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }
}

