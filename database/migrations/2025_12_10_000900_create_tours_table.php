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
        Schema::create('tours', function (Blueprint $table) {
            $table->id();

            $table->string('slug')->unique();
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable();

            $table->integer('duration_hours')->nullable();
            $table->string('city')->nullable();
            $table->string('meeting_point')->nullable();

            // borrador, publicado, o inactivo
            $table->enum('status', ['draft','published','inactive'])->default('draft');

            // capacidad
            $table->integer('min_people')->default(1);
            $table->integer('max_people')->nullable(); // capacidad máxima por salida

            // imagen principal
            $table->string('main_image_url')->nullable();

            // listas tipo ["Transportación redonda", "Guía bilingüe"]
            $table->json('included')->nullable();
            $table->json('not_included')->nullable();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
