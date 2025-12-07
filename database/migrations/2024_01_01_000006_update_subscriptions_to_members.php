<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 将 subscriptions 表从关联 users 改为关联 members
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // 先删除原来的外键约束
            $table->dropForeign(['user_id']);
            
            // 删除原来的索引
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            // 重命名 user_id 为 member_id
            $table->renameColumn('user_id', 'member_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            // 添加新的外键约束
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->onDelete('cascade');
            
            // 添加新的索引
            $table->index(['member_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropIndex(['member_id', 'status']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->renameColumn('member_id', 'user_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->index(['user_id', 'status']);
        });
    }
};

