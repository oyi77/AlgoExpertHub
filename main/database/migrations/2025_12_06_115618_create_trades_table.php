<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 255);
            $table->string('trade_type', 255);
            $table->unsignedBigInteger('user_id');
            $table->string('currency', 255);
            $table->decimal('current_price', 28, 8);
            $table->integer('duration');
            $table->dateTime('trade_stop_at');
            $table->dateTime('trade_opens_at');
            $table->string('profit_type', 255)->nullable();
            $table->decimal('profit_amount', 8, 2)->default(0.00);
            $table->decimal('loss_amount', 8, 2)->default(0.00);
            $table->decimal('charge', 28, 8)->default(0.00000000);
            $table->tinyInteger('status')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('status');
            $table->index('trade_stop_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trades');
    }
};
