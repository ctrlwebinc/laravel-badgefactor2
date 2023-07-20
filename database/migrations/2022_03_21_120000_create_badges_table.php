<?php

use Ctrlweb\BadgeFactor2\Models\Badges\BadgeCategory;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgeGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBadgesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['certification', 'attendance', 'membership', 'skill']);
            $table->string('badgeclass_id', 255);
            $table->text('title');
            $table->string('slug', 200);
            $table->longText('content');
            $table->text('criteria')->nullable();
            $table->enum('approval_type', ['approved', 'auto-approved', 'given'])->default('auto-approved');
            $table->enum('request_type', ['internal', 'external'])->default('internal');
            $table->string('request_form_url', 1024)->nullable();
            $table->foreignIdFor(BadgeCategory::class)->nullable();
            $table->foreignIdFor(BadgeGroup::class)->nullable();
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
        Schema::dropIfExists('badges');
    }
}
