<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCopyTradingSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('copy_trading_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('is_enabled')->default(false);
            $table->decimal('min_followers_balance', 20, 8)->nullable()->comment('Minimum balance required to follow');
            $table->integer('max_copiers')->nullable()->comment('Max number of followers allowed');
            $table->decimal('risk_multiplier_default', 10, 4)->default(1.0)->comment('Default risk multiplier for followers');
            $table->boolean('allow_manual_trades')->default(true)->comment('Whether to copy manual trades');
            $table->boolean('allow_auto_trades')->default(true)->comment('Whether to copy signal-based trades');
            $table->json('settings')->nullable()->comment('Additional settings');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('copy_trading_settings');
    }
}
