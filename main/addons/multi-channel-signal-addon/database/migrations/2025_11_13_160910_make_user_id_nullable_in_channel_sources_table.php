<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeUserIdNullableInChannelSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = DB::getTablePrefix() . 'channel_sources';
        $usersTable = DB::getTablePrefix() . 'users';
        
        // Get the foreign key constraint name
        $foreignKeyName = DB::getTablePrefix() . 'channel_sources_user_id_foreign';
        
        // Drop foreign key constraint first
        try {
            DB::statement("ALTER TABLE {$tableName} DROP FOREIGN KEY {$foreignKeyName}");
        } catch (\Exception $e) {
            // Foreign key might not exist or have different name, try to find it
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tableName}' AND COLUMN_NAME = 'user_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
            if (!empty($constraints)) {
                $fkName = $constraints[0]->CONSTRAINT_NAME;
                DB::statement("ALTER TABLE {$tableName} DROP FOREIGN KEY {$fkName}");
            }
        }

        // Modify column to be nullable using raw SQL
        DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN user_id BIGINT UNSIGNED NULL");

        // Re-add foreign key constraint
        Schema::table('channel_sources', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = DB::getTablePrefix() . 'channel_sources';
        $foreignKeyName = DB::getTablePrefix() . 'channel_sources_user_id_foreign';
        
        // Drop foreign key constraint first
        try {
            DB::statement("ALTER TABLE {$tableName} DROP FOREIGN KEY {$foreignKeyName}");
        } catch (\Exception $e) {
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tableName}' AND COLUMN_NAME = 'user_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
            if (!empty($constraints)) {
                $fkName = $constraints[0]->CONSTRAINT_NAME;
                DB::statement("ALTER TABLE {$tableName} DROP FOREIGN KEY {$fkName}");
            }
        }

        // Modify column back to NOT NULL (only if no null values exist)
        // First, update any null values to a default user_id if needed
        // Then make it NOT NULL
        DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN user_id BIGINT UNSIGNED NOT NULL");

        // Re-add foreign key constraint
        Schema::table('channel_sources', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
