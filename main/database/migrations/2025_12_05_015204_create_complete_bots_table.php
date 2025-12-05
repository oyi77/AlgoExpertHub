<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompleteBotsTable extends Migration
{
    public function up()
    {
        Schema::create('complete_bots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('indicators_config')->comment('EMA, RSI, PSAR settings');
            $table->json('entry_rules')->comment('Entry conditions');
            $table->json('exit_rules')->comment('SL/TP rules');
            $table->json('risk_config')->comment('Position sizing, risk %');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('downloads_count')->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('total_ratings')->default(0);
            $table->unsignedBigInteger('backtest_id')->nullable();
            $table->string('image_url')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['is_public', 'is_featured', 'avg_rating'], 'comp_bot_public_feat_rating_idx');
            $table->index('downloads_count', 'comp_bot_downloads_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('complete_bots');
    }
}
