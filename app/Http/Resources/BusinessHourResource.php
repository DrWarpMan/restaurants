<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessHourResource extends JsonResource
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
            "restaurant_id" => $this->restaurant_id,
            "day" => $this->day,
            "opens" => $this->opens,
            "closes" => $this->closes,
        ];
    }
}
