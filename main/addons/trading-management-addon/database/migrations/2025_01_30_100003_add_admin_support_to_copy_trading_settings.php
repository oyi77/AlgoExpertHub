<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminSupportToCopyTradingSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Use raw SQL to modify the column since Doctrine DBAL may not be available
        try {
            \DB::statement('ALTER TABLE `sp_copy_trading_settings` DROP FOREIGN KEY `sp_copy_trading_settings_user_id_foreign`');
        } catch (\Exception $e) {
            // Foreign key might not exist or have different name
        }
        
        try {
            \DB::statement('ALTER TABLE `sp_copy_trading_settings` DROP INDEX `sp_copy_trading_settings_user_id_unique`');
        } catch (\Exception $e) {
            // Index might not exist
        }
        
        \DB::statement('ALTER TABLE `sp_copy_trading_settings` MODIFY `user_id` BIGINT UNSIGNED NULL');
        \DB::statement('ALTER TABLE `sp_copy_trading_settings` ADD CONSTRAINT `sp_copy_trading_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE');
        
        // Check if admin_id column exists before adding
        $columns = \DB::select('SHOW COLUMNS FROM `sp_copy_trading_settings`');
        $columnNames = array_column($columns, 'Field');
        
        Schema::table('copy_trading_settings', function (Blueprint $table) use ($columnNames) {
            // Add admin_id and is_admin_owned only if they don't exist
            if (!in_array('admin_id', $columnNames)) {
            $table->unsignedBigInteger('admin_id')->nullable()->after('user_id');
            }
            if (!in_array('is_admin_owned', $columnNames)) {
            $table->boolean('is_admin_owned')->default(false)->after('admin_id');
            }
        });
        
        // Add unique constraints separately to handle nulls
        // Note: MySQL allows multiple NULLs in a unique column, so we use a composite unique index
        try {
        Schema::table('copy_trading_settings', function (Blueprint $table) {
                // Unique constraint for user-owned settings (user_id must be unique when not null)
            $table->unique('user_id', 'copy_trading_settings_user_id_unique');
        });
        } catch (\Exception $e) {
            // Index might already exist
        }
        
        try {
        Schema::table('copy_trading_settings', function (Blueprint $table) {
                // Unique constraint for admin-owned settings (admin_id must be unique when not null)
            $table->unique('admin_id', 'copy_trading_settings_admin_id_unique');
        });
        } catch (\Exception $e) {
            // Index might already exist
        }
        
        try {
        Schema::table('copy_trading_settings', function (Blueprint $table) {
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->index('admin_id');
            $table->index('is_admin_owned');
        });
        } catch (\Exception $e) {
            // Constraints might already exist
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('copy_trading_settings', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropIndex(['admin_id']);
            $table->dropIndex(['is_admin_owned']);
            $table->dropUnique('copy_trading_settings_admin_id_unique');
            $table->dropUnique('copy_trading_settings_user_id_unique');
            $table->dropColumn(['admin_id', 'is_admin_owned']);
            $table->unique('user_id');
        });
    }
}

