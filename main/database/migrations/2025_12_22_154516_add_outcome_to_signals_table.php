<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('signals', function (Blueprint $table) {
            $table->enum('outcome', ['tp_hit', 'sl_hit', 'manual_close', 'cancelled', 'open', 'expired'])->nullable()->after('is_published')->comment('Signal outcome status: TP Hit, SL Hit, Manual Close, Cancelled, Open, or Expired');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signals', function (Blueprint $table) {
            $table->dropColumn('outcome');
        });
    }
};
