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
        Schema::create('tour_tour_category', function (Blueprint $table) {
            $table->foreignId('tour_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('tour_category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->primary(['tour_id', 'tour_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_tour_category');
    }
};
