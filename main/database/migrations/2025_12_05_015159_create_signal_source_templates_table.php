<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSignalSourceTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('signal_source_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('source_type', ['telegram', 'telegram_mtproto', 'api', 'firebase', 'rss', 'web_scrape'])->default('telegram');
            $table->json('config')->comment('Connection params, parsing rules');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('downloads_count')->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('total_ratings')->default(0);
            $table->string('image_url')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['is_public', 'is_featured', 'avg_rating'], 'sig_src_public_feat_rating_idx');
            $table->index('source_type', 'sig_src_type_idx');
            $table->index('downloads_count', 'sig_src_downloads_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('signal_source_templates');
    }
}
