<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_portfolio_id')->constrained('virtual_portfolios')->onDelete('cascade');
            $table->string('symbol', 20);
            $table->enum('direction', ['buy', 'sell']);
            $table->decimal('quantity', 20, 8);
            $table->decimal('entry_price', 20, 8);
            $table->decimal('exit_price', 20, 8)->nullable();
            $table->decimal('pnl', 20, 8)->default(0);
            $table->decimal('pnl_percentage', 10, 4)->default(0);
            $table->enum('status', ['open', 'closed', 'partial'])->default('open');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['virtual_portfolio_id', 'status']);
            $table->index(['symbol']);
            $table->index(['opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_trades');
    }
};
