<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
   public function up(): void
{
   Schema::table('tour_categories', function (Blueprint $table) {
        $table->json('name_json')->nullable();
    });
}

public function down(): void
{
    DB::statement("
        ALTER TABLE tour_categories
        MODIFY name VARCHAR(255) NOT NULL
    ");
}
};
