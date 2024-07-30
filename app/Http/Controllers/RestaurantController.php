<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Services\RestaurantImportHandler;
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
     * Import new restaurant(s) via CSV file.
     */
    public function import(Request $request, RestaurantImportHandler $importHandler): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|extensions:csv|max:3000',
        ]);

        $file = $request->file('file');

        if (is_array($file)) {
            return response()->json(['message' => 'Only one file allowed'], 400);
        }

        /** @var UploadedFile $file */
        
        $imported = $importHandler->import($file);

        return response()->json([
            'success' => true,
            'imported' => $imported,
        ], 200);
    }
}
