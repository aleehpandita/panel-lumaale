<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\TourCategory;

class TourCategorySeeder extends Seeder
{
    public function run(): void
    {
        $cats = [
            'Islas',
            'Parques',
            'Chichén Itzá',
            'Aventura',
            'Snorkel',
        ];

        foreach ($cats as $name) {
            TourCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
