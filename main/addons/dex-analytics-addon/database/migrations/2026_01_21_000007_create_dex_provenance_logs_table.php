<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dex_provenance_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('watchlist_id')->nullable();
            $table->string('wallet_address')->nullable();
            $table->string('platform')->nullable();
            $table->string('source');
            $table->string('operation');
            $table->string('payload_hash');
            $table->timestamp('recorded_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['watchlist_id', 'recorded_at']);
            $table->index('platform');
            $table->index('payload_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dex_provenance_logs');
    }
};
