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
    public function index(): JsonResource
    {
        return RestaurantResource::collection(Restaurant::paginate());
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
