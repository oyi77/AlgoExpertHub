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
        Schema::create('mt_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->onDelete('cascade');
            $table->foreignId('execution_connection_id')->nullable()->constrained('execution_connections')->onDelete('set null');
            $table->enum('platform', ['MT4', 'MT5'])->default('MT4');
            $table->string('account_number')->comment('MT4/MT5 account number');
            $table->string('server')->comment('MT4/MT5 server name');
            $table->string('broker_name')->nullable();
            $table->string('api_key')->comment('mtapi.io API key');
            $table->string('account_id')->comment('mtapi.io account ID');
            $table->json('credentials')->nullable()->comment('Encrypted credentials');
            $table->decimal('balance', 20, 2)->default(0);
            $table->decimal('equity', 20, 2)->default(0);
            $table->decimal('margin', 20, 2)->default(0);
            $table->decimal('free_margin', 20, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->integer('leverage')->default(100);
            $table->enum('status', ['active', 'inactive', 'error'])->default('inactive');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'platform', 'status']);
            $table->index(['admin_id', 'platform', 'status']);
            $table->index('execution_connection_id');
            $table->unique(['account_number', 'server', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mt_accounts');
    }
};
