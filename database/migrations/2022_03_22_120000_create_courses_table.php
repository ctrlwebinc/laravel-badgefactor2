<?php

use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['internal', 'external'])->default('external');
            $table->decimal('duration');
            $table->string('url', 1024)->nullable();
            $table->string('autoevaluation_form_url', 1024)->nullable();
            $table->foreignIdFor(BadgePage::class);
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
        Schema::dropIfExists('courses');
    }
}
