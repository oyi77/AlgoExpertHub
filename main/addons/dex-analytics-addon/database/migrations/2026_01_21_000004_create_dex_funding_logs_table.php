<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dex_funding_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('watchlist_id');
            $table->string('wallet_address');
            $table->string('platform');
            $table->string('symbol');
            $table->decimal('funding_rate', 18, 8)->nullable();
            $table->decimal('funding_payment', 24, 8);
            $table->decimal('position_size', 24, 8)->nullable();
            $table->timestamp('paid_at');
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['watchlist_id', 'paid_at']);
            $table->index(['wallet_address', 'platform']);
            $table->index('symbol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dex_funding_logs');
    }
};
