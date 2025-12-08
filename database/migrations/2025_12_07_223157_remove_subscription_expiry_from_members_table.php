<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 移除 subscription_expiry 字段，改为从 subscriptions 表获取
     * 这样可以避免数据冗余和同步问题
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('subscription_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('subscription_expiry')->nullable()->after('plan');
        });
    }
};
