<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('exchange_connection_id')->constrained('execution_connections')->onDelete('cascade');
            $table->decimal('balance', 20, 8)->default(10000);
            $table->enum('market_type', ['crypto', 'fx'])->default('crypto');
            $table->string('currency', 10)->default('USDT');
            $table->decimal('initial_balance', 20, 8)->default(10000);
            $table->decimal('current_balance', 20, 8)->default(10000);
            $table->decimal('pnl', 20, 8)->default(0);
            $table->decimal('pnl_percentage', 10, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'market_type']);
            $table->index(['exchange_connection_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_portfolios');
    }
};
