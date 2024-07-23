<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'restaurant_id',
        'cuisine',
        'price',
        'rating',
        'location',
        'description',
    ];

    /**
     * Get the business hours for the restaurant.
     */
    public function businessHours(): HasMany
    {
        return $this->hasMany(BusinessHour::class)
            ->orderBy('day', 'asc')
            ->orderBy('opens', 'asc');
    }
}
