<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            // Si existen como text/string, las quitamos primero:
            if (Schema::hasColumn('tours', 'title')) $table->dropColumn('title');
            if (Schema::hasColumn('tours', 'short_description')) $table->dropColumn('short_description');
            if (Schema::hasColumn('tours', 'long_description')) $table->dropColumn('long_description');
            if (Schema::hasColumn('tours', 'meeting_point')) $table->dropColumn('meeting_point');
            if (Schema::hasColumn('tours', 'included')) $table->dropColumn('included');
            if (Schema::hasColumn('tours', 'not_included')) $table->dropColumn('not_included');

            // Re-creamos como JSON
            $table->json('title')->nullable();
            $table->json('short_description')->nullable();
            $table->json('long_description')->nullable();
            $table->json('meeting_point')->nullable();
            $table->json('included')->nullable();      // {es:[], en:[]}
            $table->json('not_included')->nullable();  // {es:[], en:[]}
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn([
                'title','short_description','long_description','meeting_point','included','not_included'
            ]);

            // Reversa simple (por si ocupas rollback)
            $table->string('title')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->text('meeting_point')->nullable();
            $table->json('included')->nullable();
            $table->json('not_included')->nullable();
        });
    }
};
