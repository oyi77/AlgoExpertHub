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
        Schema::create('filter_strategies', function (Blueprint $table) {
            $table->id();
            
            // Identity
            $table->string('name');
            $table->text('description')->nullable();
            
            // Owner & Visibility
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->enum('visibility', ['PRIVATE', 'PUBLIC_MARKETPLACE'])->default('PRIVATE');
            $table->boolean('clonable')->default(true);
            $table->boolean('enabled')->default(true);
            
            // Configuration (JSON)
            // Structure:
            // {
            //   "indicators": {
            //     "ema_fast": {"period": 10},
            //     "ema_slow": {"period": 100},
            //     "stoch": {"k": 14, "d": 3, "smooth": 3},
            //     "psar": {"step": 0.02, "max": 0.2}
            //   },
            //   "rules": {
            //     "logic": "AND",
            //     "conditions": [
            //       {"left": "ema_fast", "operator": ">", "right": "ema_slow"},
            //       {"left": "stoch", "operator": "<", "right": 80},
            //       {"left": "psar", "operator": "below_price", "right": null}
            //     ]
            //   }
            // }
            $table->json('config')->nullable();
            
            // Meta
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('created_by_user_id');
            $table->index('visibility');
            $table->index('enabled');
            $table->index('clonable');
            
            // Foreign Keys
            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('filter_strategies');
    }
};
