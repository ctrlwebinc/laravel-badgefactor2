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
        Schema::table('users', function (Blueprint $table) {
            $table->string('badgr_user_state')->nullable();
            $table->string('badgr_user_slug')->nullable();
            $table->string('badgr_password')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('badgr_user_state');
            $table->dropColumn('badgr_user_slug');
            $table->dropColumn('badgr_password');
        });
    }
};
