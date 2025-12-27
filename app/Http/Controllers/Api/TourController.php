<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TourListResource;
use App\Http\Resources\TourResource;
use App\Models\Tour;
use Illuminate\Http\Request;

class TourController extends Controller
{
    // GET /api/tours?city=Cancun&category=islas
    public function index(Request $request)
    {
        $q = Tour::query()
            ->where('status', 'published')
            ->with(['categories']);

        if ($request->filled('city')) {
            $q->where('city', $request->string('city'));
        }

        if ($request->filled('category')) {
            $catSlug = $request->string('category');
            $q->whereHas('categories', function ($qq) use ($catSlug) {
                $qq->where('slug', $catSlug);
            });
        }

        $tours = $q->orderByDesc('id')->paginate(12);

        return TourListResource::collection($tours);
    }

    // GET /api/tours/{slug}
    public function show(string $slug)
    {
        $tour = Tour::where('slug', $slug)
            ->where('status', 'published')
            ->with(['categories', 'images', 'departures', 'prices'])
            ->firstOrFail();

        return new TourResource($tour);
    }

    // GET /api/tours/{slug}/availability?date=YYYY-MM-DD
    public function availability(string $slug, Request $request)
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $tour = Tour::where('slug', $slug)
            ->where('status', 'published')
            ->with(['departures'])
            ->firstOrFail();

        $date = $request->date;

        $activeDepartures = $tour->departures->where('is_active', true)->values();

        // Si no hay horarios: tour flexible
        if ($activeDepartures->isEmpty()) {
            return response()->json([
                'tour' => $tour->slug,
                'date' => $date,
                'mode' => 'flexible',
                'slots' => [],
            ]);
        }

        $slots = $activeDepartures->map(function ($dep) use ($tour, $date) {
            $bookedPax = $tour->bookings()
                ->where('tour_date', $date)
                ->where('tour_departure_id', $dep->id)
                ->where('status', '!=', 'cancelled')
                ->selectRaw('COALESCE(SUM(pax_adults + pax_children + pax_infants), 0) as total')
                ->value('total');

            $max = $tour->max_people ?? 999999;

            return [
                'departure_id' => $dep->id,
                'departure_time' => $dep->departure_time,
                'capacity' => $max,
                'booked' => (int)$bookedPax,
                'available' => max($max - (int)$bookedPax, 0),
            ];
        });

        return response()->json([
            'tour' => $tour->slug,
            'date' => $date,
            'mode' => 'scheduled',
            'slots' => $slots,
        ]);
    }
}
