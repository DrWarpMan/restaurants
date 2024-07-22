<?php

namespace App\Http\Controllers;

use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Services\RestaurantImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\UploadedFile;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResource
    {
        $status = $request->get('status', null);
        $name = $request->get('name', null);
        $cuisine = $request->get('cuisine', null);

        $query = Restaurant::query();

        if ($status) {
            $today = now()->dayOfWeekIso;
            $secondOfTheDay = now()->secondOfDay;

            $callback = function ($query) use ($today, $secondOfTheDay) {
                $query
                    ->where('day', $today)
                    ->where('opens', '<=', $secondOfTheDay)
                    ->where('closes', '>=', $secondOfTheDay);
            };

            if($status === "open") {
                $query->whereHas('businessHours', $callback);
            } else if($status === "closed") {
                $query->whereDoesntHave('businessHours', $callback);
            }
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
