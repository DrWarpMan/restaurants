<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class BusinessHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'day',
        'opens',
        'closes',
    ];
    
    public $timestamps = false;

    public function pretty(): string
    {
        return "{$this->getDayOfWeek()} {$this->getOpenTime()} - {$this->getCloseTime()}";
    }

    private function getDayOfWeek(): string
    {
        return match ($this->day) {
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
            7 => 'Sun',
            default => throw new LogicException("Invalid day")
        };
    }

    private function getOpenTime(): string
    {
        return gmdate('H:i', $this->opens);
    }

    private function getCloseTime(): string
    {
        return gmdate('H:i', $this->closes);
    }
}
