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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tour_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('tour_date');

            // Para tours con horario fijo -> se usa
            // Para tours sin horario fijo -> puede ir NULL
            $table->foreignId('tour_departure_id')
                ->nullable()
                ->constrained('tour_departures')
                ->nullOnDelete();

            // Datos del cliente
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');

            // Pax
            $table->integer('pax_adults');
            $table->integer('pax_children')->default(0);
            $table->integer('pax_infants')->default(0);

            // Monto total calculado
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('USD');

            // Estado de la reserva y del pago
            $table->enum('status', ['pending','confirmed','cancelled'])
                ->default('pending');

            $table->enum('payment_status', ['pending','paid','failed','refunded'])
                ->default('pending');

            $table->string('payment_method')->nullable(); // stripe, cash, etc.

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
