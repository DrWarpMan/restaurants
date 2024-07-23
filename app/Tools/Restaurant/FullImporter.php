<?php

declare(strict_types=1);

namespace App\Tools\Restaurant;

use App\Models\Restaurant;
use App\Tools\BusinessHour\Util;
use Exception;
use App\Tools\Restaurant\Validator;

class FullImporter implements Importer
{
    public function importRow(array $columns): void
    {
        $restaurant = new Restaurant([
            'name' => $columns[0] ?? '',
            'restaurant_id' => $columns[1] ?? '',
            'cuisine' => $columns[2] ?? '',
            'price' => $columns[6] ?? '',
            'rating' => $columns[7] ?? '',
            'location' => $columns[8] ?? '',
            'description' => $columns[9] ?? '',
        ]);

        (new Validator($restaurant))->validate();

        $restaurant->save();

        $this->importBusinessHours(
            $restaurant,
            $columns[3] ?? '',
            $columns[4] ?? '',
            $columns[5] ?? ''
        );
    }

    /**
     * @param Restaurant $restaurant Restaurant to import business hours for.
     * @param string $opens e.g. 9:00:00
     * @param string $closes e.g. 17:00:00
     * @param string $daysOpen e.g. Mo,Tu,We,Fr,Sa
     */
    private function importBusinessHours(
        Restaurant $restaurant,
        string $opens, // 9:00:00
        string $closes, // 17:00:00
        string $daysOpen // Mo,Tu,We,Fr,Sa
    ) {
        // Convert days to integers
         
        $days = [];

        foreach (explode(",", $daysOpen) as $day) {
            $dayInt = Util::dayToInt($day);

            if ($dayInt === false) {
                throw new Exception("Invalid day");
            }
            
            $days[] = $dayInt;
        }

        // Convert times to seconds of the day
        [$timeStart, $timeEnd] = [$this->processTime($opens), $this->processTime($closes)];

        if($timeEnd === 0) {
            $timeEnd = 86400;
        }

        Util::createMultiple($restaurant->id, $days, $timeStart, $timeEnd);

        Util::mergeBusinessHours($restaurant->id);
    }

    /**
     * Convert time string to seconds of the day.
     * 
     * @param string $input 24-hour format (from '0:00:00' to '23:59:59').
     * @return int Returns integer between 0-86399.
     */
    private function processTime(string $input): int
    {
        $matches = [];
        $result = preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $input, $matches);

        if ($result !== 1) {
            throw new Exception("Invalid time format");
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];
        $second = (int) $matches[3];

        if ($hour < 0 || $hour > 23) {
            throw new Exception("Invalid hour: $hour");
        }

        if ($minute < 0 || $minute > 59) {
            throw new Exception("Invalid minute: $minute");
        }

        if ($second < 0 || $second > 59) {
            throw new Exception("Invalid second: $second");
        }

        return $hour * 3600 + $minute * 60 + $second;
    }
}
