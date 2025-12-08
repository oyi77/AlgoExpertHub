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
        Schema::create('user_onboarding_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('welcome_seen')->default(false);
            $table->boolean('profile_completed')->default(false);
            $table->boolean('plan_subscribed')->default(false);
            $table->boolean('signal_source_added')->default(false);
            $table->boolean('trading_connection_setup')->default(false);
            $table->boolean('trading_preset_created')->default(false);
            $table->boolean('first_deposit_made')->default(false);
            $table->boolean('onboarding_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Indexes
            $table->index('user_id');
            $table->index('onboarding_completed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_onboarding_progress');
    }
};
