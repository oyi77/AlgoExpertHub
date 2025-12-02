<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelSourcePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_source_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_source_id');
            $table->unsignedBigInteger('plan_id');
            $table->timestamps();

            $table->foreign('channel_source_id')->references('id')->on('channel_sources')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
            $table->unique(['channel_source_id', 'plan_id']);
            
            $table->index('channel_source_id');
            $table->index('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_source_plans');
    }
}

