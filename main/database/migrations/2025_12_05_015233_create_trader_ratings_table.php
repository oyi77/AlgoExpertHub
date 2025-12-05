<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTraderRatingsTable extends Migration
{
    public function up()
    {
        Schema::create('trader_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trader_id');
            $table->unsignedBigInteger('follower_id');
            $table->tinyInteger('rating')->comment('1-5 stars');
            $table->text('review')->nullable();
            $table->boolean('verified_follower')->default(false);
            $table->integer('helpful_votes')->default(0);
            $table->timestamps();

            $table->foreign('trader_id')->references('id')->on('trader_profiles')->onDelete('cascade');
            $table->foreign('follower_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['trader_id', 'follower_id'], 'trader_rating_uniq');
            $table->index(['trader_id', 'rating'], 'trader_rating_trader_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trader_ratings');
    }
}
