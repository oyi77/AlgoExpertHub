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
        Schema::table('configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('configurations', 'bot_url')) {
                $table->string('bot_url')->nullable()->after('decimal_precision');
            }
            if (!Schema::hasColumn('configurations', 'telegram_token')) {
                $table->string('telegram_token')->nullable()->after('bot_url');
            }
            if (!Schema::hasColumn('configurations', 'allow_telegram')) {
                $table->boolean('allow_telegram')->default(false)->after('telegram_token');
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
        Schema::table('configurations', function (Blueprint $table) {
            if (Schema::hasColumn('configurations', 'bot_url')) {
                $table->dropColumn('bot_url');
            }
            if (Schema::hasColumn('configurations', 'telegram_token')) {
                $table->dropColumn('telegram_token');
            }
            if (Schema::hasColumn('configurations', 'allow_telegram')) {
                $table->dropColumn('allow_telegram');
            }
        });
    }
};
