<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('userbots', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->unique();
            $table->string('session')->unique();
            $table->string('last_auth_status')->nullable();
            $table->string('current_status')->nullable();
            $table->json('listen_peers')->nullable();
            $table->boolean('need_admin_interact')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('userbots');
    }
};
