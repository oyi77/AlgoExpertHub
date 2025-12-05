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
        Schema::create('signal_take_profits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('signal_id');
            $table->tinyInteger('tp_level')->comment('TP level number (1, 2, 3, etc.)');
            $table->decimal('tp_price', 28, 8)->comment('Take profit price for this level');
            $table->decimal('tp_percentage', 5, 2)->nullable()->comment('Percentage of total position to close at this TP');
            $table->decimal('lot_percentage', 5, 2)->nullable()->comment('Percentage of lot size for this TP (alternative to tp_percentage)');
            $table->boolean('is_closed')->default(false)->comment('Whether this TP level has been hit');
            $table->timestamp('closed_at')->nullable()->comment('When this TP was hit');
            $table->timestamps();

            // Foreign key
            $table->foreign('signal_id')->references('id')->on('signals')->onDelete('cascade');
            
            // Indexes
            $table->index(['signal_id', 'tp_level']);
            $table->index(['signal_id', 'is_closed']);
            
            // Unique constraint: one TP level per signal
            $table->unique(['signal_id', 'tp_level']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('signal_take_profits');
    }
};
