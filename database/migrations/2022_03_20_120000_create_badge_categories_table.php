<?php

use Ctrlweb\BadgeFactor2\Models\Course;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBadgeCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('badge_categories', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->text('subtitle');
            $table->text('description');
            $table->string('slug', 200);
            $table->string('image');
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
        Schema::dropIfExists('badge_categories');
    }
}
