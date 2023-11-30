<?php

use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
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
        BadgePage::where('last_updated_at', '!=', null)->update(['last_updated_at' => null]);
        Schema::table('badge_pages', function (Blueprint $table) {
            $table->date('last_updated_at')->nullable()->change();
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
            $table->string('last_updated_at')->nullable()->change();
        });
    }
};
