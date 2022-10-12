<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name');
            $table->string('last_name');
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('slug');
            $table->unsignedBigInteger('wp_id')->nullable();
            $table->string('wp_password', 60)->nullable();
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
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
            $table->dropColumn('description');
            $table->dropColumn('website');
            $table->dropColumn('slug');
            $table->dropColumn('wp_id');
            $table->dropColumn('wp_password');
        });
    }
}
