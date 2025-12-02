<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendChannelSourcesForAdminOwnership extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if columns already exist before adding them
        if (!Schema::hasColumn('channel_sources', 'is_admin_owned')) {
            Schema::table('channel_sources', function (Blueprint $table) {
                $table->boolean('is_admin_owned')->default(0)->after('user_id');
            });
        }
        
        if (!Schema::hasColumn('channel_sources', 'scope')) {
            Schema::table('channel_sources', function (Blueprint $table) {
                $table->enum('scope', ['user', 'plan', 'global'])->nullable()->after('is_admin_owned');
            });
        }
        
        // Add index if column exists (will be handled by separate migration if needed)
        // Note: user_id nullable change is handled by separate migration
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channel_sources', function (Blueprint $table) {
            $table->dropIndex(['is_admin_owned']);
            $table->dropColumn(['is_admin_owned', 'scope']);
        });
    }
}

