<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20)->comment('版本号，如 1.1.0');
            $table->string('platform', 20)->default('win')->comment('平台: win, mac, linux');
            $table->string('file_path')->nullable()->comment('安装包存储路径');
            $table->string('file_name')->nullable()->comment('安装包文件名');
            $table->unsignedBigInteger('file_size')->default(0)->comment('文件大小(字节)');
            $table->string('sha512')->nullable()->comment('SHA512 校验值');
            $table->text('release_notes')->nullable()->comment('更新日志');
            $table->boolean('is_mandatory')->default(false)->comment('是否强制更新');
            $table->boolean('is_published')->default(false)->comment('是否发布');
            $table->timestamp('published_at')->nullable()->comment('发布时间');
            $table->unsignedBigInteger('download_count')->default(0)->comment('下载次数');
            $table->timestamps();

            $table->unique(['version', 'platform']);
            $table->index(['platform', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};

