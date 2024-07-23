<?php

namespace App\Tools\BusinessHour;

use App\Models\BusinessHour;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class Util
{
    /**
     * Create a new BusinessHour instance, validate it and save it to the database.
     
     * @param array $attributes Attributes to pass to the BusinessHour constructor.
     * @throws ValidationException 
     */
    public static function createWithValidation(array $attributes): void {
        $businessHour = new BusinessHour($attributes);

        (new Validator($businessHour))->validate();

        $businessHour->save();
    }

    /**
     * Merge overlapping business hours.
     * 
     * @param string $restaurantId Restaurant ID to merge business hours for.
     */
    public static function mergeBusinessHours(string $restaurantId): void {
        $businessHours = BusinessHour::where('restaurant_id', $restaurantId)
            ->orderBy('day', 'asc')
            ->orderBy('opens', 'asc')
            ->get();

        $toDelete = [];
        $merged = [];

        $last = null;

        foreach ($businessHours as $businessHour) {
            if ($last === null) {
                $last = $businessHour;
                continue;
            }

            // If the last business hour closes at the same time as the current one opens, merge them
            if ($last->day === $businessHour->day && $last->closes === $businessHour->opens) {
                $last->closes = $businessHour->closes;
                $toDelete[] = $businessHour;
            } else {
                // Can not merge, move on to the next one
                $merged[] = $last;
                $last = $businessHour;
            }
        }

        $merged[] = $last;

        // Merge

        DB::beginTransaction();

        try {
            foreach ($toDelete as $businessHour) {
                $businessHour->delete();
            }

            foreach ($merged as $businessHour) {
                $businessHour->save();
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Convert an English day name to an integer. Accepts full and abbreviated names (2 and 3 letter format).
     * 
     * @param string $day Day name, case-sensitive. ("Mon", "Tuesday", or "Su").
     * @return int|bool Integer representation of the day (1-7) or false if the input is invalid.
     */
    public static function dayToInt(string $day): int|bool {
        return match ($day) {
            'Mo', "Mon", 'Monday' => 1,
            'Tu', "Tue", 'Tuesday' => 2,
            'We', "Wed", 'Wednesday' => 3,
            'Th', "Thu", 'Thursday' => 4,
            'Fr', "Fri", 'Friday' => 5,
            'Sa', "Sat", 'Saturday' => 6,
            'Su', "Sun", 'Sunday' => 7,
            default => false,
        };
    }
}
