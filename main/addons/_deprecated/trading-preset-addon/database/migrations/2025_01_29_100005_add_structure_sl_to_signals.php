<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStructureSlToSignals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('signals', function (Blueprint $table) {
            $table->decimal('structure_sl_price', 28, 8)->nullable()->after('tp')->comment('Structure-based SL price (for sl_mode=STRUCTURE)');
            $table->index('structure_sl_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('signals', function (Blueprint $table) {
            $table->dropIndex(['structure_sl_price']);
            $table->dropColumn('structure_sl_price');
        });
    }
}

