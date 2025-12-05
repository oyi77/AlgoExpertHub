<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCacheMetadataToMarketDataTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('market_data')) {
            return;
        }

        Schema::table('market_data', function (Blueprint $table) {
            $table->integer('access_count')->default(0)->after('source_type');
            $table->timestamp('last_accessed_at')->nullable()->after('access_count');
            
            // Add composite index for multi-user access optimization
            $table->index(['symbol', 'timeframe', 'timestamp'], 'multi_user_access_idx');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('market_data')) {
            return;
        }

        Schema::table('market_data', function (Blueprint $table) {
            $table->dropIndex('multi_user_access_idx');
            $table->dropColumn(['access_count', 'last_accessed_at']);
        });
    }
}
