<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pagebuilder_menus')) {
            Schema::create('pagebuilder_menus', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->json('structure')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('pagebuilder_menus');
    }
};
