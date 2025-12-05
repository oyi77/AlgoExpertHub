<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('bot_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Creator (null if admin-owned)');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['grid', 'dca', 'martingale', 'scalping', 'trend_following', 'breakout', 'mean_reversion'])->default('scalping');
            $table->json('config')->comment('Grid levels, DCA steps, risk settings');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->decimal('price', 10, 2)->default(0)->comment('0=free');
            $table->integer('downloads_count')->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('total_ratings')->default(0);
            $table->unsignedBigInteger('backtest_id')->nullable();
            $table->string('image_url')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['is_public', 'is_featured', 'avg_rating'], 'bot_tmpl_public_feat_rating_idx');
            $table->index('category', 'bot_tmpl_category_idx');
            $table->index('downloads_count', 'bot_tmpl_downloads_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bot_templates');
    }
}
