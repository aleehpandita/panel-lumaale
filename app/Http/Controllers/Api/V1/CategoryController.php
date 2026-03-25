<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TourCategory;

class CategoryController extends Controller
{
    public function index()
    {
        $cats = TourCategory::query()
            ->orderBy('id')
            ->get(['name', 'slug']);

        return response()->json([
            'data' => $cats,
        ]);
    }
}