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
        Schema::create('srm_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->comment('Test name');
            $table->text('description')->nullable()->comment('Test description');
            $table->enum('status', ['draft', 'running', 'paused', 'completed', 'cancelled'])->default('draft')->comment('Test status');
            
            // Test Configuration
            $table->decimal('pilot_group_percentage', 5, 2)->default(10.00)->comment('Percentage of users in pilot group');
            $table->unsignedInteger('test_duration_days')->default(14)->comment('Test duration in days');
            
            // SRM Logic Variants
            $table->json('control_logic')->nullable()->comment('Control group SRM logic (current production)');
            $table->json('pilot_logic')->nullable()->comment('Pilot group SRM logic (new logic to test)');
            
            // Results
            $table->date('start_date')->nullable()->comment('Test start date');
            $table->date('end_date')->nullable()->comment('Test end date');
            $table->unsignedInteger('pilot_group_size')->default(0)->comment('Pilot group size');
            $table->unsignedInteger('control_group_size')->default(0)->comment('Control group size');
            
            // Performance Comparison
            $table->decimal('pilot_avg_pnl', 10, 2)->nullable()->comment('Pilot group average P/L');
            $table->decimal('control_avg_pnl', 10, 2)->nullable()->comment('Control group average P/L');
            $table->decimal('pilot_avg_drawdown', 5, 2)->nullable()->comment('Pilot group average drawdown');
            $table->decimal('control_avg_drawdown', 5, 2)->nullable()->comment('Control group average drawdown');
            $table->decimal('pilot_win_rate', 5, 2)->nullable()->comment('Pilot group win rate');
            $table->decimal('control_win_rate', 5, 2)->nullable()->comment('Control group win rate');
            
            // Statistical Significance
            $table->decimal('p_value', 8, 6)->nullable()->comment('Statistical significance test result');
            $table->boolean('is_significant')->default(false)->comment('Is result statistically significant');
            
            // Decision
            $table->enum('decision', ['deploy', 'reject', 'extend'])->nullable()->comment('Test decision');
            $table->text('decision_notes')->nullable()->comment('Decision notes');
            
            // Metadata
            $table->unsignedBigInteger('created_by_admin_id')->nullable()->comment('Admin who created the test');
            $table->timestamps();
            
            // Indexes
            $table->index('status', 'idx_status');
            $table->index(['start_date', 'end_date'], 'idx_dates');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('srm_ab_tests');
    }
};

