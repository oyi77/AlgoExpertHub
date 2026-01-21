<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dex_copy_suitability', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('watchlist_id');
            $table->string('wallet_address');
            $table->string('platform');
            $table->unsignedInteger('score')->default(0);
            $table->decimal('trade_frequency', 10, 4)->nullable();
            $table->decimal('avg_position_size', 24, 8)->nullable();
            $table->decimal('max_drawdown', 10, 4)->nullable();
            $table->decimal('profit_factor', 10, 4)->nullable();
            $table->timestamp('computed_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['watchlist_id', 'computed_at']);
            $table->index(['wallet_address', 'platform']);
            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dex_copy_suitability');
    }
};
