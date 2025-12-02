<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelSourceUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_source_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_source_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('channel_source_id')->references('id')->on('channel_sources')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['channel_source_id', 'user_id']);
            
            $table->index('channel_source_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_source_users');
    }
}

