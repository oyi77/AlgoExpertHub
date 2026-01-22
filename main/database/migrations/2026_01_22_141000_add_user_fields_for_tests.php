<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only add columns that don't exist yet
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'kyc_status')) {
                $table->enum('kyc_status', ['unverified', 'pending', 'approved'])->default('unverified')->after('is_kyc_verified');
            }
            if (!Schema::hasColumn('users', 'telegram_chat_id')) {
                $table->unsignedBigInteger('telegram_chat_id')->nullable()->after('kyc_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'telegram_chat_id')) {
                $table->dropColumn('telegram_chat_id');
            }
            if (Schema::hasColumn('users', 'kyc_status')) {
                $table->dropColumn('kyc_status');
            }
        });
    }
};
