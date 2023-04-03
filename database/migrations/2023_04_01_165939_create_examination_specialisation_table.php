<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('examination_specialisation', function (Blueprint $table) {
            $table->integer('examination_id')->unsigned();
            $table->integer('specialisation_id')->unsigned();
            // $table->foreign('examination_id')->references('id')->on('examinations')
            // ->onDelete('cascade');
            // $table->foreign('specialisation_id')->references('id')->on('specialisations')
            // ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examination_specialisation    ');
    }
};
