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
        Schema::table('trading_presets', function (Blueprint $table) {
            // Determine position - after filter_strategy_id if exists, otherwise at end
            $afterColumn = Schema::hasColumn('trading_presets', 'filter_strategy_id') 
                ? 'filter_strategy_id' 
                : null;
            
            // AI Model Profile reference
            if ($afterColumn) {
                $table->unsignedBigInteger('ai_model_profile_id')->nullable()->after($afterColumn);
            } else {
                $table->unsignedBigInteger('ai_model_profile_id')->nullable();
            }
            
            // AI Confirmation Settings
            $table->enum('ai_confirmation_mode', ['NONE', 'REQUIRED', 'ADVISORY'])->default('NONE')->after('ai_model_profile_id');
            $table->decimal('ai_min_safety_score', 5, 2)->nullable()->after('ai_confirmation_mode'); // 0-100
            
            // AI Position Management
            $table->boolean('ai_position_mgmt_enabled')->default(false)->after('ai_min_safety_score');
            
            // Indexes
            $table->index('ai_model_profile_id');
            $table->index('ai_confirmation_mode');
            
            // Foreign Key (only if ai_model_profiles table exists)
            if (Schema::hasTable('ai_model_profiles')) {
                $table->foreign('ai_model_profile_id')
                    ->references('id')
                    ->on('ai_model_profiles')
                    ->onDelete('set null');
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
        Schema::table('trading_presets', function (Blueprint $table) {
            $table->dropForeign(['ai_model_profile_id']);
            $table->dropIndex(['ai_model_profile_id']);
            $table->dropIndex(['ai_confirmation_mode']);
            $table->dropColumn([
                'ai_model_profile_id',
                'ai_confirmation_mode',
                'ai_min_safety_score',
                'ai_position_mgmt_enabled',
            ]);
        });
    }
};
