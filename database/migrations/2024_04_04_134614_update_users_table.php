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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('description_visible')
                ->after('description')
                ->default(true);

            $table->boolean('website_visible')
                ->after('website')
                ->default(true);

            $table->boolean('place_visible')
                ->after('place')
                ->default(true);

            $table->boolean('organisation_visible')
                ->after('organisation')
                ->default(true);

            $table->boolean('job_visible')
                ->after('job')
                ->default(true);

            $table->boolean('biography_visible')
                ->after('biography')
                ->default(true);

            $table->boolean('facebook_visible')
                ->after('facebook')
                ->default(true);

            $table->boolean('twitter_visible')
                ->after('twitter')
                ->default(true);

            $table->boolean('linkedin_visible')
                ->after('linkedin')
                ->default(true);
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
            $table->dropColumn('description_visible');
            $table->dropColumn('website_visible');
            $table->dropColumn('place_visible');
            $table->dropColumn('organisation_visible');
            $table->dropColumn('job_visible');
            $table->dropColumn('biography_visible');
            $table->dropColumn('facebook_visible');
            $table->dropColumn('twitter_visible');
            $table->dropColumn('linkedin_visible');
        });
    }
};
