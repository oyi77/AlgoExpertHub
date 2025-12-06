<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExecutionNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('execution_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('For user notifications');
            $table->unsignedBigInteger('admin_id')->nullable()->comment('For admin notifications');
            $table->unsignedBigInteger('connection_id');
            $table->unsignedBigInteger('signal_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->enum('type', ['execution', 'open', 'close', 'error', 'sl_hit', 'tp_hit', 'liquidation'])->default('execution');
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->json('metadata')->nullable()->comment('Additional data');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('connection_id')->references('id')->on('execution_connections')->onDelete('cascade');
            $table->foreign('signal_id')->references('id')->on('signals')->onDelete('set null');
            $table->foreign('position_id')->references('id')->on('execution_positions')->onDelete('set null');

            $table->index('user_id');
            $table->index('admin_id');
            $table->index('connection_id');
            $table->index('is_read');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('execution_notifications');
    }
}

