<?php

declare(strict_types=1);

namespace App\Tools\Restaurant;

use App\Models\Restaurant;
use App\Tools\BusinessHour\Util;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BasicImporter implements Importer
{
    public function importRow(array $columns): void
    {
        $name = $columns[0] ?? '';
        $slug = Str::of($name)->slug('-')->toString();

        $restaurant = new Restaurant([
            'name' => $name,
            'restaurant_id' => $slug,
        ]);

        (new Validator($restaurant))->validate();

        $restaurant->save();

        $this->importBusinessHours($restaurant, $columns[1] ?? '');
    }

    /**
     * @param Restaurant $restaurant Restaurant to import business hours for.
     * @param string $input Example input: "Mon-Thu, Sun 11:30 am - 9 pm / Fri-Sat 11:30 am - 3:30 am"
     * @throws ValidationException
     */
    private function importBusinessHours(
        Restaurant $restaurant,
        string $input
    ): void{
        $parts = explode("/", $input);

        foreach($parts as $part) {
            $part = trim($part);

            $matches = [];
            // Split in half on first digit occurence
            $result = preg_match('/^(.+?)\s+(\d.*)$/', $part, $matches);

            if($result !== 1) {
                throw ValidationException::withMessages(['Invalid business hours format']);
            }
            
            // Convert days to integers, and "unzip" ranges (e.g. "Mon, Wed-Sat" => [1,3,4,5,6])
            $days = $this->processDays($matches[1]); // [1,3,4,7]
            // Convert time to seconds of the day
            [$timeStart, $timeEnd] = $this->processTime($matches[2]); // e.g.: [41400, 75600]

            Util::createMultiple($restaurant->id, $days, $timeStart, $timeEnd);
        }
        
        Util::mergeBusinessHours($restaurant->id);
    }

    /**
     * Convert day(s) or range of days into day integers.
     * 
     * @param string $input Example input: "Mon, Wed-Sat"
     * @return array<int> Returns an array of integers representing days (1-7), sorted in ascending order. E.g. [1,3,4,5,6]
     * @throws ValidationException
     */
    private function processDays(string $input): array
    {   
        $days = [];

        foreach(explode(',', $input) as $split) {
            $split = trim($split);

            $matches = [];
            // Match separate days or day ranges (e.g. "Mon" or "Wed-Sat")
            $result = preg_match('/^([a-zA-Z]{3})(?:-([a-zA-Z]{3}))?$/', $split, $matches);

            if($result !== 1) {
                throw ValidationException::withMessages(['Invalid day format']);
            }

            $dayStart = $matches[1];
            $dayEnd = $matches[2] ?? $dayStart;

            $dayStartInt = Util::dayToInt($dayStart);
            $dayEndInt = Util::dayToInt($dayEnd);

            if($dayStartInt === false || $dayEndInt === false) {
                throw ValidationException::withMessages(['Invalid day name']);
            }

            if($dayStartInt > $dayEndInt) {
                throw ValidationException::withMessages(['Invalid day range']);
            }

            for($i = $dayStartInt; $i <= $dayEndInt; $i++) {
                $days[] = $i;
            }
        }

        sort($days, SORT_NUMERIC);

        return $days;
    }

    /**
     * Extract time range from the provided input.
     * 
     * @param string $input e.g. "12:00 am - 12:00 pm"
     * @return array{int, int} Returns a 2-element tuple with start and end time in seconds of the day.
     * @throws ValidationException
     */
    private function processTime(string $input): array
    {
        $matches = [];
        // Find all time strings in the provided string, case-insensitive
        $result = preg_match_all('/\d{1,2}(?::\d{2})? (?:am|pm)/i', $input, $matches);

        // There should be exactly 2 time strings (start & end)
        if ($result !== 2) {
            throw ValidationException::withMessages(['Invalid time format']);
        }

        $timeStart = $matches[0][0]; // 12:00 am
        $timeEnd = $matches[0][1]; // 12:00 pm

        $timeStartSeconds = $this->convertTimeToSeconds($timeStart); // 0
        $timeEndSeconds = $this->convertTimeToSeconds($timeEnd); // 43200

        if($timeEndSeconds === 0) {
            $timeEndSeconds = 86400;
        }

        return [$timeStartSeconds, $timeEndSeconds];
    }

    /**
     * Convert time string to seconds of the day.
     * 
     * @param string $input 12-hour format (from '0:00 am' to '11:59 pm').
     * @return int Returns integer between 0-86399.
     * @throws ValidationException
     */
    private function convertTimeToSeconds(string $input): int {
        // am/pm is case-insensitive
        $input = strtolower($input);

        [$time, $amPm] = explode(" ", $input);

        $hoursMinutes = explode(":", $time);

        $hours = (int) $hoursMinutes[0];
        $minutes = (int) ($hoursMinutes[1] ?? 0);

        if($hours < 1 || $hours > 12) {
            throw ValidationException::withMessages(['Invalid hour']);
        }

        if($minutes < 0 || $minutes > 59) {
            throw ValidationException::withMessages(['Invalid minute']);
        }

        if($hours === 12) {
            $hours = 0;
        }

        if($amPm === 'pm') {
            $hours += 12;
        }

        return $hours * 3600 + $minutes * 60;
    }
}
