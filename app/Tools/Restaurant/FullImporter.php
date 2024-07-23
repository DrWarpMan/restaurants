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

        Util::mergeBusinessHours($restaurant->id);
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
        // Convert times to seconds of the day
        [$timeStart, $timeEnd] = [$this->processTime($opens), $this->processTime($closes)];

        // When restaurant is open past midnight and "overflows" to the next day
        // (e.g. [12:00 pm - 1:00 am] => [43200, 3600])
        if ($timeEnd <= $timeStart) {
            $callback = function($day) use ($timeStart, $timeEnd, $restaurant) {
                Util::createWithValidation([
                    'restaurant_id' => $restaurant->id,
                    'day' => $day,
                    'opens' => $timeStart,
                    'closes' => 86400,
                ]);

                // Edge case when restaurant closes at exactly midnight
                // (e.g. [1:00 am - 12:00 am] => [3600, 0] OR [12:00 am - 12:00 am] => [0, 0])
                if($timeEnd === 0) {
                    return;
                }

                $tomorrow = ($day % 7) + 1;

                Util::createWithValidation([
                    'restaurant_id' => $restaurant->id,
                    'day' => $tomorrow,
                    'opens' => 0,
                    'closes' => $timeEnd,
                ]);
            };
        // When restaurant opens and closes on the same day
        } else {
            $callback = function($day) use ($timeStart, $timeEnd, $restaurant) {
                Util::createWithValidation([
                    'restaurant_id' => $restaurant->id,
                    'day' => $day,
                    'opens' => $timeStart,
                    'closes' => $timeEnd,
                ]);
            };
        }

        foreach (explode(",", $daysOpen) as $day) {
            $dayInt = Util::dayToInt($day);

            if ($dayInt === false) {
                throw new Exception("Invalid day");
            }

            $callback($dayInt);
        }
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
