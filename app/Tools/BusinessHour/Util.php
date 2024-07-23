<?php

declare(strict_types=1);

namespace App\Tools\BusinessHour;

use App\Models\BusinessHour;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
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
     * Tries to merge adjacent business hours for a restaurant. Uses transaction to ensure data integrity.
     * 
     * @param int $restaurantId Restaurant ID to merge business hours for.
     * @return bool True if any business hours were merged, false otherwise.
     */
    public static function mergeBusinessHours(int $restaurantId): bool {
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

        // Merged anything?
        if (count($toDelete) === 0) {
            return false;
        }

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

        return true;
    }

    /**
     * Creates multiple business hours at once. Validates and saves them to the database.
     * 
     * @param int $restaurantId Restaurant ID to create business hours for.
     * @param array $days Array of unique integers representing days of the week (1-7, 1 = Monday, 7 = Sunday).
     * @param int $opensAt Opening time. In seconds since midnight (0-86399).
     * @param int $closesAt Closing time. In seconds since midnight (1-86400). Can be less or equal to $opensAt - will be treated as closing on the next day ("overflow").
     * @throws ValidationException
     * @throws InvalidArgumentException out-of-range values
     * @see Util::createWithValidation()
     */
    public static function createMultiple(int $restaurantId, array $days, int $opensAt, int $closesAt): void
    {
        if($opensAt < 0 || $opensAt > 86399) {
            throw new InvalidArgumentException('Invalid opening time. Must be an integer between 0 and 86399.');
        }

        if($closesAt < 1 || $closesAt > 86400) {
            throw new InvalidArgumentException('Invalid closing time. Must be an integer between 1 and 86400.');
        }

        foreach($days as $day) {
            if(!is_int($day) || $day < 1 || $day > 7) {
                throw new InvalidArgumentException('Invalid day of the week. Must be an integer between 1 and 7.');
            }

            // When restaurant is open past midnight and "overflows" to the next day
            // (e.g. [12:00 pm - 1:00 am] => [43200, 3600] OR [5:00 am - 5:00 am] => [18000, 18000])
            if ($closesAt <= $opensAt) {
                $callback = function($day) use ($opensAt, $closesAt, $restaurantId) {
                    Util::createWithValidation([
                        'restaurant_id' => $restaurantId,
                        'day' => $day,
                        'opens' => $opensAt,
                        'closes' => 86400,
                    ]);

                    $tomorrow = ($day % 7) + 1;

                    Util::createWithValidation([
                        'restaurant_id' => $restaurantId,
                        'day' => $tomorrow,
                        'opens' => 0,
                        'closes' => $closesAt,
                    ]);
                };
            // When restaurant opens and closes on the same day
            } else {
                $callback = function($day) use ($opensAt, $closesAt, $restaurantId) {
                    Util::createWithValidation([
                        'restaurant_id' => $restaurantId,
                        'day' => $day,
                        'opens' => $opensAt,
                        'closes' => $closesAt,
                    ]);
                };
            }

            foreach($days as $day) {
                $callback($day);
            }
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
