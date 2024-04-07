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
            $table->string('new_email_validation_token')
                ->after('email')
                ->nullable();

            $table->string('new_email')
                ->after('email')
                ->nullable();

            $table->string('new_establishment_id')
                ->after('establishment_id')
                ->nullable();

            $table->string('new_job')
                ->after('job')
                ->nullable();
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
            $table->dropColumn('new_email');
            $table->dropColumn('new_email_validation_token');
            $table->dropColumn('new_establishment_id');
            $table->dropColumn('new_job');
        });
    }
};
