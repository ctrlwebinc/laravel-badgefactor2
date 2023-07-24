<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('digital_tool_type_education_strategy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('education_strategy_id')->nullable();
            $table->foreignId('digital_tool_type_id')->nullable();
            $table->timestamps();

            $table->foreign('education_strategy_id', 'dtt_es_id')
                ->references('id')
                ->on('education_strategies')
                ->cascadeOnDelete();
            $table->foreign('digital_tool_type_id', 'dtt_id')
                ->references('id')
                ->on('digital_tool_types')
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
        Schema::dropIfExists('digital_tool_type_education_strategy');
    }
};
