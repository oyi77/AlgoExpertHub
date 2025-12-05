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
        // Check if pages table exists and doesn't already have the column
        if (Schema::hasTable('pages') && !Schema::hasColumn('pages', 'pagebuilder_page_id')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->unsignedBigInteger('pagebuilder_page_id')->nullable()->after('id');
                // Foreign key will be added after pagebuilder_pages table is created
                // $table->foreign('pagebuilder_page_id')->references('id')->on('pagebuilder_pages')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('pages') && Schema::hasColumn('pages', 'pagebuilder_page_id')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropForeign(['pagebuilder_page_id']);
                $table->dropColumn('pagebuilder_page_id');
            });
        }
    }
};
