<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\{Tour, TourImage, TourDeparture, TourPrice, TourCategory};

class TourSeeder extends Seeder
{
    public function run(): void
    {
        $islas   = TourCategory::where('slug', 'islas')->first();
        $parques = TourCategory::where('slug', 'parques')->first();
        $chichen = TourCategory::where('slug', 'chichen-itza')->first();

        // --- TOUR 1 (con horarios, niño paga, infante gratis) ---
        $tour1 = Tour::updateOrCreate(
            ['slug' => 'isla-mujeres-catamaran'],
            [
                'title' => 'Isla Mujeres Catamaran',
                'short_description' => 'Catamarán, snorkel y beach club.',
                'long_description' => 'Tour completo a Isla Mujeres con snorkel, barra libre y club de playa.',
                'duration_hours' => 8,
                'city' => 'Cancún',
                'meeting_point' => 'Marina (se confirma al reservar)',
                'status' => 'published',
                'min_people' => 1,
                'max_people' => 30,
                'main_image_url' => 'https://picsum.photos/seed/isla1/1200/800',
                'included' => ['Transporte', 'Snorkel', 'Barra libre', 'Beach club'],
                'not_included' => ['Impuesto de muelle', 'Propinas'],
            ]
        );

        $tour1->categories()->sync(array_filter([$islas?->id]));

        // imágenes
        foreach ([1,2,3,4,5] as $i) {
            TourImage::create([
                'tour_id' => $tour1->id,
                'url' => "https://picsum.photos/seed/isla{$i}/1200/800",
                'sort_order' => $i,
            ]);
        }

        // horarios (solo este tour)
        foreach (['09:00:00', '13:00:00'] as $t) {
            TourDeparture::create([
                'tour_id' => $tour1->id,
                'departure_time' => $t,
                'is_active' => true,
            ]);
        }

        // precios (base + temporada alta)
        TourPrice::create([
            'tour_id' => $tour1->id,
            'name' => 'Tarifa base',
            'start_date' => null,
            'end_date' => null,
            'price_adult' => 89.00,
            'price_child' => 69.00,
            'price_infant' => 0.00, // infante gratis
            'currency' => 'USD',
        ]);

        TourPrice::create([
            'tour_id' => $tour1->id,
            'name' => 'Temporada alta',
            'start_date' => '2025-12-15',
            'end_date' => '2026-01-10',
            'price_adult' => 105.00,
            'price_child' => 80.00,
            'price_infant' => 0.00,
            'currency' => 'USD',
        ]);

        // --- TOUR 2 (sin horarios, solo adultos, no niños/infantes) ---
        $tour2 = Tour::updateOrCreate(
            ['slug' => 'private-vip-tour'],
            [
                'title' => 'Private VIP Tour (Solo Adultos)',
                'short_description' => 'Experiencia privada premium.',
                'long_description' => 'Tour privado con horario flexible (a coordinar).',
                'duration_hours' => 6,
                'city' => 'Riviera Maya',
                'meeting_point' => 'Pick-up en hotel (a coordinar)',
                'status' => 'published',
                'min_people' => 1,
                'max_people' => 10,
                'main_image_url' => 'https://picsum.photos/seed/vip/1200/800',
                'included' => ['Pick-up privado', 'Bebidas', 'Guía'],
                'not_included' => ['Propinas'],
            ]
        );

        $tour2->categories()->sync(array_filter([$parques?->id]));

        foreach ([1,2,3] as $i) {
            TourImage::create([
                'tour_id' => $tour2->id,
                'url' => "https://picsum.photos/seed/vip{$i}/1200/800",
                'sort_order' => $i,
            ]);
        }

        // precio: child/infant NULL => no se permiten
        TourPrice::create([
            'tour_id' => $tour2->id,
            'name' => 'Tarifa base',
            'start_date' => null,
            'end_date' => null,
            'price_adult' => 220.00,
            'price_child' => null,
            'price_infant' => null,
            'currency' => 'USD',
        ]);

        // --- TOUR 3 (con horarios, niño paga, infante paga) ---
        $tour3 = Tour::updateOrCreate(
            ['slug' => 'chichen-itza-classic'],
            [
                'title' => 'Chichén Itzá Classic',
                'short_description' => 'Tour clásico a Chichén Itzá.',
                'long_description' => 'Incluye transporte, guía y parada adicional.',
                'duration_hours' => 12,
                'city' => 'Cancún',
                'meeting_point' => 'Pick-up en hotel / punto de encuentro',
                'status' => 'published',
                'min_people' => 1,
                'max_people' => 40,
                'main_image_url' => 'https://picsum.photos/seed/chichen/1200/800',
                'included' => ['Transporte', 'Guía', 'Snack'],
                'not_included' => ['Entradas', 'Propinas'],
            ]
        );

        $tour3->categories()->sync(array_filter([$chichen?->id]));

        foreach ([1,2,3,4] as $i) {
            TourImage::create([
                'tour_id' => $tour3->id,
                'url' => "https://picsum.photos/seed/chichen{$i}/1200/800",
                'sort_order' => $i,
            ]);
        }

        foreach (['07:00:00'] as $t) {
            TourDeparture::create([
                'tour_id' => $tour3->id,
                'departure_time' => $t,
                'is_active' => true,
            ]);
        }

        TourPrice::create([
            'tour_id' => $tour3->id,
            'name' => 'Tarifa base',
            'start_date' => null,
            'end_date' => null,
            'price_adult' => 99.00,
            'price_child' => 79.00,
            'price_infant' => 15.00, // infante paga
            'currency' => 'USD',
        ]);
    }
}
