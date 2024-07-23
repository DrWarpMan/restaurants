<?php

namespace App\Tools\Restaurant;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Validator as FacadesValidator;

class Validator
{
    private const RULES = [
        'name' => ['required', 'string', 'max:255'],
        'restaurant_id' => ['required', 'string', 'max:255'],
        'cuisine' => [ 'string', 'max:255'],
        'price' => [ 'numeric', 'integer', 'min:1', 'max:5'],
        'rating' => [ 'numeric', 'integer', 'min:1', 'max:5'],
        'location' => [ 'string', 'max:255'],
        'description' => [ 'string'],
    ];

    public function __construct(
        private readonly Restaurant $restaurant
    ) {}

    /**
     * Validate the restaurant data.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(): void
    {
        FacadesValidator::make($this->restaurant->toArray(), self::RULES)->validate();
    }
}
