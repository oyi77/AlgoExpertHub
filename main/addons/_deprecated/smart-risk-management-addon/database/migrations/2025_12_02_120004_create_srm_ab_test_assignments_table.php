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
        Schema::create('srm_ab_test_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ab_test_id')->comment('FK to srm_ab_tests');
            $table->unsignedBigInteger('user_id')->nullable()->comment('FK to users');
            $table->unsignedBigInteger('connection_id')->nullable()->comment('FK to execution_connections');
            $table->enum('group_type', ['pilot', 'control'])->comment('Group assignment');
            
            $table->timestamp('assigned_at')->useCurrent()->comment('When user was assigned');
            
            // Indexes
            $table->index(['ab_test_id', 'group_type'], 'idx_ab_test');
            $table->index('user_id', 'idx_user');
            $table->index('connection_id', 'idx_connection');
            
            // Foreign Keys
            $table->foreign('ab_test_id')
                ->references('id')
                ->on('srm_ab_tests')
                ->onDelete('cascade');
            
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            }
            
            if (Schema::hasTable('execution_connections')) {
                $table->foreign('connection_id')
                    ->references('id')
                    ->on('execution_connections')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('srm_ab_test_assignments');
    }
};

