<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('badgr_configs', function (Blueprint $table) {
            $table->id();
            $table->string('badgr_server_base_url');
            $table->string('client_id');
            $table->string('client_secret');
            $table->string('password_client_id');
            $table->string('password_client_secret');
            $table->string('token_set', 1024)->default('N;');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('badgr_configs');
    }
};
