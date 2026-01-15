<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            if (!Schema::hasColumn('tours', 'included')) {
                $table->json('included')->nullable();
            }

            if (!Schema::hasColumn('tours', 'not_included')) {
                $table->json('not_included')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            if (Schema::hasColumn('tours', 'included')) {
                $table->dropColumn('included');
            }

            if (Schema::hasColumn('tours', 'not_included')) {
                $table->dropColumn('not_included');
            }
        });
    }
};
