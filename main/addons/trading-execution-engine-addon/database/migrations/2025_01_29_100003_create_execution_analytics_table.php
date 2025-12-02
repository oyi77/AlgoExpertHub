<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExecutionAnalyticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('execution_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('connection_id');
            $table->unsignedBigInteger('user_id')->nullable()->comment('For user analytics');
            $table->unsignedBigInteger('admin_id')->nullable()->comment('For admin analytics');
            $table->date('date');
            $table->unsignedInteger('total_trades')->default(0);
            $table->unsignedInteger('winning_trades')->default(0);
            $table->unsignedInteger('losing_trades')->default(0);
            $table->decimal('total_pnl', 20, 8)->default(0);
            $table->decimal('win_rate', 5, 2)->default(0)->comment('Percentage');
            $table->decimal('profit_factor', 10, 4)->default(0);
            $table->decimal('max_drawdown', 10, 4)->default(0)->comment('Percentage');
            $table->decimal('balance', 20, 8)->default(0);
            $table->decimal('equity', 20, 8)->default(0);
            $table->json('additional_metrics')->nullable()->comment('Sharpe ratio, expectancy, etc.');
            $table->timestamps();

            $table->foreign('connection_id')->references('id')->on('execution_connections')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');

            $table->unique(['connection_id', 'date']);
            $table->index('user_id');
            $table->index('admin_id');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('execution_analytics');
    }
}

