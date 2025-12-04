<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'telegram_chat_id')) {
                $table->string('telegram_chat_id')->nullable()->index();
            }
            if (!Schema::hasColumn('users', 'phone_country_code')) {
                $table->string('phone_country_code')->nullable();
            }
        });

        Schema::table('signals', function (Blueprint $table) {
            $table->index(['is_published', 'published_date']);
        });

        Schema::table('plan_subscriptions', function (Blueprint $table) {
            $table->index(['user_id', 'is_current', 'plan_expired_at']);
        });

        Schema::table('user_signals', function (Blueprint $table) {
            $table->unique(['user_id', 'signal_id']);
        });

        Schema::table('dashboard_signals', function (Blueprint $table) {
            $table->unique(['user_id', 'signal_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'telegram_chat_id')) {
                $table->dropColumn('telegram_chat_id');
            }
            if (Schema::hasColumn('users', 'phone_country_code')) {
                $table->dropColumn('phone_country_code');
            }
        });

        Schema::table('signals', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'published_date']);
        });

        Schema::table('plan_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_current', 'plan_expired_at']);
        });

        Schema::table('user_signals', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'signal_id']);
        });

        Schema::table('dashboard_signals', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'signal_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
        });
    }
};

