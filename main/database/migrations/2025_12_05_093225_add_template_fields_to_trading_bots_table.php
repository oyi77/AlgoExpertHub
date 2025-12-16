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
        Schema::table('trading_bots', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('trading_bots', 'is_template')) {
                $table->boolean('is_template')->default(false)->after('is_active');
            }
            // visibility enum should match existing migration (PRIVATE, PUBLIC_MARKETPLACE)
            // Check if visibility column exists, if not add it with correct enum values
            if (!Schema::hasColumn('trading_bots', 'visibility')) {
                $table->enum('visibility', ['PRIVATE', 'PUBLIC_MARKETPLACE'])->default('PRIVATE')->after('is_template');
            }
            if (!Schema::hasColumn('trading_bots', 'parent_bot_id')) {
                $table->unsignedBigInteger('parent_bot_id')->nullable()->after('visibility');
            }
            if (!Schema::hasColumn('trading_bots', 'is_admin_owned')) {
                $table->boolean('is_admin_owned')->default(false)->after('parent_bot_id');
            }
            
            // Add foreign key and index only if parent_bot_id column exists
            if (Schema::hasColumn('trading_bots', 'parent_bot_id')) {
                // Check if foreign key doesn't exist
                $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('trading_bots');
                $hasForeignKey = false;
                foreach ($foreignKeys as $fk) {
                    if (in_array('parent_bot_id', $fk->getLocalColumns())) {
                        $hasForeignKey = true;
                        break;
                    }
                }
                if (!$hasForeignKey) {
                    $table->foreign('parent_bot_id')->references('id')->on('trading_bots')->onDelete('set null');
                }
                
                // Add index if it doesn't exist
                $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('trading_bots');
                $hasIndex = false;
                foreach ($indexes as $idx) {
                    if ($idx->getName() === 'trading_bots_is_template_visibility_index' || 
                        (in_array('is_template', $idx->getColumns()) && in_array('visibility', $idx->getColumns()))) {
                        $hasIndex = true;
                        break;
                    }
                }
                if (!$hasIndex && Schema::hasColumn('trading_bots', 'is_template') && Schema::hasColumn('trading_bots', 'visibility')) {
                    $table->index(['is_template', 'visibility']);
                }
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
        Schema::table('trading_bots', function (Blueprint $table) {
            $table->dropForeign(['parent_bot_id']);
            $table->dropIndex(['is_template', 'visibility']);
            $table->dropColumn(['is_template', 'visibility', 'parent_bot_id', 'is_admin_owned']);
        });
    }
};
