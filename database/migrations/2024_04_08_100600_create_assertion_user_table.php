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
        Schema::create('assertion_user', function (Blueprint $table) {
            $userClass = config('badgefactor2.user_model');
            $table->id();
            $table->string('assertion_id');
            $table->foreignIdFor($userClass);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['assertion_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assertion_user');
    }
};
