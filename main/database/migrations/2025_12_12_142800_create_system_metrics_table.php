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
        Schema::create('system_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name')->index();
            $table->decimal('metric_value', 20, 8);
            $table->enum('metric_type', ['counter', 'gauge', 'histogram']);
            $table->json('tags')->nullable();
            $table->timestamp('timestamp')->index();
            $table->timestamps();

            $table->index(['metric_name', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_metrics');
    }
};
