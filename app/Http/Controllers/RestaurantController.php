<?php

namespace App\Http\Controllers;

use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResource
    {
        $open = $request->get('open', null) === "true";
        $name = $request->get('name', null);
        $cuisine = $request->get('cuisine', null);

        $query = Restaurant::query();

        if ($open) {
            $today = now()->dayOfWeekIso;
            $secondOfTheDay = now()->secondOfDay;

            $query->whereHas('businessHours', function ($query) use ($today, $secondOfTheDay) {
                $query
                    ->where('day', $today)
                    ->where('opens', '<=', $secondOfTheDay)
                    ->where('closes', '>=', $secondOfTheDay);
            });
        }

        if ($name !== null) {
            $query->where('name', 'like', "%$name%");
        }

        if ($cuisine !== null) {
            $query->where('cuisine', 'like', "%$cuisine%");
        }

        return RestaurantResource::collection($query->paginate(perPage: 10));
    }

    /**
     * Display the specified resource.
     */
    public function show(Restaurant $restaurant): JsonResource
    {
        return new RestaurantResource($restaurant);
    }

    /**
     * Import new restaurant(s) via CSV.
     */
    public function import(Request $request)
    {
        //
    }
}
