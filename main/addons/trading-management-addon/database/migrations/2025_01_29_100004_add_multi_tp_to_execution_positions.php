<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMultiTpToExecutionPositions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('execution_positions', function (Blueprint $table) {
            // Multi-TP prices
            $table->decimal('tp1_price', 20, 8)->nullable()->after('tp_price');
            $table->decimal('tp2_price', 20, 8)->nullable();
            $table->decimal('tp3_price', 20, 8)->nullable();
            
            // Close percentages
            $table->decimal('tp1_close_pct', 5, 2)->nullable()->comment('Percentage to close at TP1 (0-100)');
            $table->decimal('tp2_close_pct', 5, 2)->nullable();
            $table->decimal('tp3_close_pct', 5, 2)->nullable();
            
            // Close timestamps
            $table->timestamp('tp1_closed_at')->nullable();
            $table->timestamp('tp2_closed_at')->nullable();
            $table->timestamp('tp3_closed_at')->nullable();
            
            // Closed quantities
            $table->decimal('tp1_closed_qty', 20, 8)->nullable()->comment('Quantity closed at TP1');
            $table->decimal('tp2_closed_qty', 20, 8)->nullable();
            $table->decimal('tp3_closed_qty', 20, 8)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('execution_positions', function (Blueprint $table) {
            $table->dropColumn([
                'tp1_price',
                'tp2_price',
                'tp3_price',
                'tp1_close_pct',
                'tp2_close_pct',
                'tp3_close_pct',
                'tp1_closed_at',
                'tp2_closed_at',
                'tp3_closed_at',
                'tp1_closed_qty',
                'tp2_closed_qty',
                'tp3_closed_qty',
            ]);
        });
    }
}

