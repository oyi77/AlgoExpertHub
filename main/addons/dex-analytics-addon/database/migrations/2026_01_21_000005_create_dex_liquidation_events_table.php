<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dex_liquidation_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('watchlist_id');
            $table->string('wallet_address');
            $table->string('platform');
            $table->string('symbol');
            $table->string('side');
            $table->decimal('liquidation_price', 24, 8)->nullable();
            $table->decimal('position_size', 24, 8)->nullable();
            $table->decimal('loss_amount', 24, 8)->nullable();
            $table->timestamp('liquidated_at');
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['watchlist_id', 'liquidated_at']);
            $table->index(['wallet_address', 'platform']);
            $table->index('symbol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dex_liquidation_events');
    }
};
