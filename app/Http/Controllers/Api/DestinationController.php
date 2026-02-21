<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DestinationListResource;
use App\Http\Resources\DestinationResource;
use App\Models\Destination;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    // GET /api/v1/destinations
    public function index(Request $request)
    {
        $q = Destination::query()
            ->withCount(['tours' => function ($qq) {
                $qq->where('status', 'published'); // si quieres contar solo publicados
            }]);

        // Si tienes status en destinos, filtra:
        // $q->where('status', 'published');

        $destinations = $q->orderBy('name')->get();

        return DestinationListResource::collection($destinations);
    }

    // GET /api/v1/destinations/{slug}
    public function show(string $slug)
    {
        $destination = Destination::where('slug', $slug)
            ->withCount(['tours' => fn ($qq) => $qq->where('status', 'published')])
            ->firstOrFail();

        return new DestinationResource($destination);
    }
}