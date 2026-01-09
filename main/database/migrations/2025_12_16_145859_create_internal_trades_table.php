<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Check if table already exists (may exist with different prefix)
        if (!Schema::hasTable('internal_trades')) {
            Schema::create('internal_trades', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('symbol', 20);
                $table->enum('direction', ['buy', 'sell']);
                $table->decimal('quantity', 20, 8);
                $table->decimal('entry_price', 20, 8);
                $table->decimal('current_price', 20, 8)->nullable();
                $table->decimal('sl_price', 20, 8)->nullable();
                $table->decimal('tp_price', 20, 8)->nullable();
                $table->decimal('pnl', 20, 8)->default(0);
                $table->enum('status', ['open', 'closed'])->default('open');
                $table->timestamp('opened_at');
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['user_id', 'status']);
                $table->index('symbol');
                $table->index('status');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('internal_trades');
    }
};
