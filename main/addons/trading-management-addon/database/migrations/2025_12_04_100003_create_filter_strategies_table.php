<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create filter_strategies table
 * 
 * Migrated from filter-strategy-addon to trading-management-addon
 * For storing technical indicator filter strategies
 */
class CreateFilterStrategiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('filter_strategies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable()->comment('User who created this strategy');
            $table->enum('visibility', ['PRIVATE', 'PUBLIC_MARKETPLACE'])->default('PRIVATE');
            $table->boolean('clonable')->default(false)->comment('Allow other users to clone');
            $table->boolean('enabled')->default(true);
            $table->json('config')->comment('Indicators and rules configuration');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('created_by_user_id');
            $table->index('visibility');
            $table->index('enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('filter_strategies');
    }
}

