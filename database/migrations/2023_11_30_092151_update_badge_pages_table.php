<?php

use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
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
            $table->string('last_updated_at')->after('video_url')->nullable();
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
            $table->dropColumn('last_updated_at');
        });
    }
};
