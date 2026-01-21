<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dex_trader_watchlist', function (Blueprint $table): void {
            $table->id();
            $table->string('wallet_address');
            $table->string('platform');
            $table->string('status')->default('active');
            $table->boolean('is_active')->default(true);
            $table->integer('position_count')->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamps();

            $table->index(['wallet_address', 'platform']);
            $table->index('status');
            $table->index('assigned_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dex_trader_watchlist');
    }
};
