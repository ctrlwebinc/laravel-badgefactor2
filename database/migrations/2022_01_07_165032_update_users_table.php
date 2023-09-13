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
            $table->dropColumn('name');
            $table->string('first_name');
            $table->string('last_name');
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('slug');
            $table->unsignedBigInteger('wp_id')->nullable();
            $table->string('wp_password', 60)->nullable();

            $table->string('username')->nullable();
            $table->string('place')->nullable();
            $table->string('organisation')->nullable();
            $table->string('job')->nullable();
            $table->text('biography')->nullable();
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('photo')->nullable();
            $table->string('billing_last_name')->nullable();
            $table->string('billing_first_name')->nullable();
            $table->string('billing_society')->nullable();
            $table->string('billing_address_line_1')->nullable();
            $table->string('billing_address_line_2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_postal_code')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_phone')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('user_status')->default('ACTIVE')->nullable();
            $table->string('wp_application_password')->nullable();
            $table->string('badgr_token_set',1024)->nullable();
            $table->timestamp('last_connexion')->nullable();
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
            $table->string('name');
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
            $table->dropColumn('description');
            $table->dropColumn('website');
            $table->dropColumn('slug');
            $table->dropColumn('wp_id');
            $table->dropColumn('wp_password');

            $table->dropColumn('username');
            $table->dropColumn('place');
            $table->dropColumn('job');
            $table->dropColumn('biography');
            $table->dropColumn('facebook');
            $table->dropColumn('twitter');
            $table->dropColumn('linkedin');
            $table->dropColumn('photo');
            $table->dropColumn('billing_last_name');
            $table->dropColumn('billing_first_name');
            $table->dropColumn('billing_society');
            $table->dropColumn('billing_address_line_1');
            $table->dropColumn('billing_address_line_2');
            $table->dropColumn('billing_city');
            $table->dropColumn('billing_postal_code');
            $table->dropColumn('billing_country');
            $table->dropColumn('billing_state');
            $table->dropColumn('billing_phone');
            $table->dropColumn('billing_email');
            $table->dropColumn('user_status');
            $table->dropColumn('wp_application_password');
            $table->dropColumn('last_connexion');
        });
    }
}
