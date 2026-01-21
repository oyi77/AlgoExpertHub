<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dex_trader_labels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('watchlist_id');
            $table->string('wallet_address');
            $table->string('platform');
            $table->string('label');
            $table->decimal('confidence', 5, 2)->default(0);
            $table->unsignedInteger('window_days')->nullable();
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->index(['watchlist_id', 'computed_at']);
            $table->index(['wallet_address', 'platform']);
            $table->index('label');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dex_trader_labels');
    }
};
