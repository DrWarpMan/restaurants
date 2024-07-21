<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory;

    /**
     * Get the business hours for the restaurant.
     */
    public function businessHours(): HasMany
    {
        return $this->hasMany(BusinessHour::class);
    }
}
