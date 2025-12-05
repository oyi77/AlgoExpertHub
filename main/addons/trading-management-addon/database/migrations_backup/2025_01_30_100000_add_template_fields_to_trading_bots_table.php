<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add template fields to trading_bots table
 * 
 * Adds fields for prebuilt bot templates:
 * - visibility: PRIVATE or PUBLIC_MARKETPLACE
 * - clonable: Whether template can be cloned
 * - is_default_template: System default templates
 * - created_by_user_id: User who created (null for system templates)
 * - suggested_connection_type: Hint for connection type (fx, crypto, both)
 * - tags: JSON array for categorization
 */
class AddTemplateFieldsToTradingBotsTable extends Migration
{
    public function up()
    {
        // Skip if table doesn't exist
        if (!Schema::hasTable('trading_bots')) {
            return;
        }

        Schema::table('trading_bots', function (Blueprint $table) {
            // Template/Visibility fields
            $table->enum('visibility', ['PRIVATE', 'PUBLIC_MARKETPLACE'])->default('PRIVATE')->after('win_rate');
            $table->boolean('clonable')->default(true)->after('visibility');
            $table->boolean('is_default_template')->default(false)->after('clonable');
            $table->unsignedBigInteger('created_by_user_id')->nullable()->after('is_default_template');
            $table->enum('suggested_connection_type', ['fx', 'crypto', 'both'])->nullable()->after('created_by_user_id');
            $table->json('tags')->nullable()->after('suggested_connection_type');

            // Indexes
            $table->index('visibility');
            $table->index('is_default_template');
            $table->index('created_by_user_id');
            $table->index('clonable');

            // Foreign key
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('trading_bots')) {
            return;
        }

        Schema::table('trading_bots', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropIndex(['visibility']);
            $table->dropIndex(['is_default_template']);
            $table->dropIndex(['created_by_user_id']);
            $table->dropIndex(['clonable']);
            
            $table->dropColumn([
                'visibility',
                'clonable',
                'is_default_template',
                'created_by_user_id',
                'suggested_connection_type',
                'tags',
            ]);
        });
    }
}
