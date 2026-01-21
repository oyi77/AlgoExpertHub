<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dex_analytics_cache', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('watchlist_id');
            $table->string('wallet_address');
            $table->string('platform');
            $table->string('metric_key');
            $table->json('metric_value');
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->index(['watchlist_id', 'computed_at']);
            $table->index(['wallet_address', 'platform']);
            $table->index('metric_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dex_analytics_cache');
    }
};
