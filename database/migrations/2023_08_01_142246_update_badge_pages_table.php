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
        Schema::table('badge_pages', function (Blueprint $table) {
            $table->string('video_url')->after('badge_category_id')->nullable();
            $table->string('image')->after('badge_category_id')->nullable();
            $table->boolean('duration')->after('badge_category_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('badge_pages', function (Blueprint $table) {
            $table->dropColumn('video_url');
            $table->dropColumn('image');
            $table->dropColumn('duration');
        });
    }
};
