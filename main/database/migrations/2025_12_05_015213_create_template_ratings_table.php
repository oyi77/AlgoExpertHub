<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateRatingsTable extends Migration
{
    public function up()
    {
        Schema::create('template_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('template_type', ['bot', 'signal', 'complete']);
            $table->unsignedBigInteger('template_id');
            $table->tinyInteger('rating')->comment('1-5 stars');
            $table->text('review')->nullable();
            $table->boolean('verified_purchase')->default(false);
            $table->integer('helpful_votes')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'template_type', 'template_id'], 'unique_user_template_rating');
            $table->index(['template_type', 'template_id', 'rating']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('template_ratings');
    }
}
