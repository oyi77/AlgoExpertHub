<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeConfigColumnToTextInChannelSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = DB::getTablePrefix() . 'channel_sources';
        
        // Change config column from JSON to TEXT to store encrypted strings
        DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN config TEXT COMMENT 'Encrypted credentials, URLs, selectors, etc.'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = DB::getTablePrefix() . 'channel_sources';
        
        // Change back to JSON (only if all values are valid JSON)
        DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN config JSON COMMENT 'Encrypted credentials, URLs, selectors, etc.'");
    }
}
