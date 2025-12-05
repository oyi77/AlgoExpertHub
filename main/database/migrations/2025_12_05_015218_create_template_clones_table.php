<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateClonesTable extends Migration
{
    public function up()
    {
        Schema::create('template_clones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('template_type', ['bot', 'signal', 'complete']);
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('original_id')->comment('Points to original template');
            $table->json('cloned_config')->comment('User customizations');
            $table->boolean('is_active')->default(true);
            $table->string('custom_name')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_active'], 'tmpl_clone_user_active_idx');
            $table->index(['template_type', 'template_id'], 'tmpl_clone_type_id_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('template_clones');
    }
}
