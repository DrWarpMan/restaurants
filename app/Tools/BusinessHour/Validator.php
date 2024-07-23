<?php

namespace App\Tools\BusinessHour;

use App\Models\BusinessHour;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Validation\ValidationException;

class Validator
{
    private const RULES = [
        'restaurant_id' => ['required', 'integer'],
        'day' => ['required', 'integer', 'min:1', 'max:7'],
        'opens' => ['required', 'integer', 'min:0', 'max:86400'],
        'closes' => ['required', 'integer', 'min:0', 'max:86400'],
    ];

    public function __construct(
        private readonly BusinessHour $businessHour
    ) {}

    private function hasValidInterval(): bool {
        return $this->businessHour->opens < $this->businessHour->closes;
    }

    /**
     * Check if the business hour overlaps with any other business hour record
     * for the same restaurant and day.
     * 
     * Note: This method assumes that the provided business hour is valid.
     */
    private function hasOverlap(): bool
    {
        // If 2 provided intervals are valid (A_from < A_to && B_from < B_to),
        // we can be sure that intervals don't overlap if one of the following is true:
        // - interval A starts AFTER interval B ends
        // - interval A ends BEFORE interval B starts

        return BusinessHour::where('restaurant_id', $this->businessHour->restaurant_id)
            ->where('day', $this->businessHour->day)
            ->whereNot(function ($query) {
                $query->where('opens', '>=', $this->businessHour->closes)
                    ->orWhere('closes', '<=', $this->businessHour->opens);
            })
            ->exists();
    }

    /**
     * Validate the business hour data. Checks for invalid intervals, or overlaps with other business hours.
     * 
     * @throws ValidationException
     */
    public function validate(): void
    {
        FacadesValidator::make($this->businessHour->toArray(), self::RULES)->validate();

        if (!$this->hasValidInterval()) {
            throw ValidationException::withMessages([
                'opens' => 'The opening time must be before the closing time.',
                'closes' => 'The closing time must be after the opening time.'
            ]);
        }

        if ($this->hasOverlap()) {
            throw ValidationException::withMessages([
                'opens' => 'The business hours overlap with other business hours.',
            ]);
        }
    }
}
