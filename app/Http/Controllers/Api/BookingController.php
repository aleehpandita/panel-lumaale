<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Tour;
use App\Models\TourPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    // POST /api/bookings
    public function store(Request $request)
    {
        $data = $request->validate([
            'tour_slug' => ['required', 'string'],
            'tour_date' => ['required', 'date'],
            'departure_id' => ['nullable', 'integer'],
            'pax_adults' => ['required', 'integer', 'min:1'],
            'pax_children' => ['nullable', 'integer', 'min:0'],
            'pax_infants' => ['nullable', 'integer', 'min:0'],
            'customer_name' => ['required', 'string'],
            'customer_email' => ['required', 'email'],
            'customer_phone' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $tour = Tour::where('slug', $data['tour_slug'])
            ->where('status', 'published')
            ->with(['departures'])
            ->firstOrFail();

        $date = $data['tour_date'];

        $paxAdults = (int)$data['pax_adults'];
        $paxChildren = (int)($data['pax_children'] ?? 0);
        $paxInfants = (int)($data['pax_infants'] ?? 0);

        // Seleccionar tarifa por fecha (temporada preferente)
        $price = TourPrice::where('tour_id', $tour->id)
            ->where(function ($q) use ($date) {
                $q->whereNull('start_date')->whereNull('end_date')
                  ->orWhere(function ($q2) use ($date) {
                      $q2->where('start_date', '<=', $date)
                         ->where('end_date', '>=', $date);
                  });
            })
            ->orderByRaw('start_date IS NULL ASC')
            ->firstOrFail();

        // Flexibilidad: NULL = no permitido
        if (is_null($price->price_child) && $paxChildren > 0) {
            return response()->json(['message' => 'Este tour no permite niños.'], 422);
        }
        if (is_null($price->price_infant) && $paxInfants > 0) {
            return response()->json(['message' => 'Este tour no permite infantes.'], 422);
        }

        // Horarios
        $activeDepartures = $tour->departures->where('is_active', true)->values();
        $departureId = $data['departure_id'] ?? null;

        if ($activeDepartures->isNotEmpty()) {
            if (!$departureId) {
                return response()->json(['message' => 'Debes elegir un horario de salida.'], 422);
            }
            if (!$activeDepartures->firstWhere('id', (int)$departureId)) {
                return response()->json(['message' => 'Horario inválido para este tour.'], 422);
            }
        } else {
            $departureId = null;
        }

        // Total
        $total = ($paxAdults * (float)$price->price_adult)
               + ($paxChildren * (float)($price->price_child ?? 0))
               + ($paxInfants * (float)($price->price_infant ?? 0));

        // Cupo (solo si hay horarios)
        if ($departureId) {
            $totalPax = $paxAdults + $paxChildren + $paxInfants;
            $max = $tour->max_people ?? 999999;

            $bookedPax = Booking::where('tour_id', $tour->id)
                ->where('tour_date', $date)
                ->where('tour_departure_id', $departureId)
                ->where('status', '!=', 'cancelled')
                ->selectRaw('COALESCE(SUM(pax_adults + pax_children + pax_infants), 0) as total')
                ->value('total');

            if (((int)$bookedPax + $totalPax) > $max) {
                return response()->json(['message' => 'No hay disponibilidad suficiente para ese horario.'], 422);
            }
        }

        $booking = DB::transaction(function () use ($tour, $date, $departureId, $data, $paxAdults, $paxChildren, $paxInfants, $total, $price) {
            return Booking::create([
                'tour_id' => $tour->id,
                'tour_date' => $date,
                'tour_departure_id' => $departureId,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'pax_adults' => $paxAdults,
                'pax_children' => $paxChildren,
                'pax_infants' => $paxInfants,
                'total_amount' => $total,
                'currency' => $price->currency ?? 'USD',
                'status' => 'pending',
                'payment_status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return (new BookingResource($booking))->response()->setStatusCode(201);
    }
}
