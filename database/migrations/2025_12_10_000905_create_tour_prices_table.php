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
        Schema::create('tour_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')
                ->constrained()
                ->cascadeOnDelete();

            // Ej: "Tarifa normal", "Temporada alta"
            $table->string('name')->nullable();

            // Si ambos son null → tarifa base que aplica siempre
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Adulto SIEMPRE
            $table->decimal('price_adult', 10, 2);

            // null = no se permite, 0 = gratis, >0 = con costo
            $table->decimal('price_child', 10, 2)->nullable();
            $table->decimal('price_infant', 10, 2)->nullable();

            $table->string('currency', 3)->default('USD');

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_prices');
    }
};
