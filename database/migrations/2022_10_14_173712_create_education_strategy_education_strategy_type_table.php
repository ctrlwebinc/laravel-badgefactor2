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
        Schema::create('education_strategy_education_strategy_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('education_strategy_id')->nullable();
            $table->foreignId('education_strategy_type_id')->nullable();
            $table->timestamps();

            $table->foreign('education_strategy_id', 'es_id')
                ->references('id')
                ->on('education_strategies')
                ->cascadeOnDelete();
            $table->foreign('education_strategy_type_id', 'est_id')
                ->references('id')
                ->on('education_strategy_types')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('education_strategy_education_strategy_type');
    }
};
