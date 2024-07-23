<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "restaurant_id" => $this->restaurant_id,
            "cuisine" => $this->cuisine,
            "price" => $this->price,
            "rating" => $this->rating,
            "location" => $this->location,
            "description" => $this->description,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "business_hours" => BusinessHourResource::collection($this->businessHours),
        ];
    }
}
