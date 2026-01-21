<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dex_pnl_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('watchlist_id');
            $table->string('wallet_address');
            $table->string('platform');
            $table->string('symbol');
            $table->string('side');
            $table->decimal('entry_price', 24, 8)->nullable();
            $table->decimal('exit_price', 24, 8)->nullable();
            $table->decimal('size', 24, 8)->nullable();
            $table->decimal('realized_pnl', 24, 8);
            $table->decimal('fees', 24, 8)->nullable();
            $table->decimal('funding_cost', 24, 8)->nullable();
            $table->timestamp('closed_at');
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['watchlist_id', 'closed_at']);
            $table->index(['wallet_address', 'platform']);
            $table->index('symbol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dex_pnl_records');
    }
};
