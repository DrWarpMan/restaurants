<?php

namespace Database\Seeders;

use App\Models\BusinessHour;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Restaurant::truncate();
        BusinessHour::truncate();
        Schema::enableForeignKeyConstraints();
        
        $restaurants = Restaurant::factory()
            ->count(100)
            ->create();

        $restaurants->each(function (Restaurant $restaurant) {
            $businessDays = fake()->randomElements(range(1, 7), null);

            foreach ($businessDays as $day) {
                BusinessHour::factory()
                    ->create([
                        "restaurant_id" => $restaurant->id,
                        "day" => $day,
                        "opens" => fake()->numberBetween(0, 86400 / 2), // 0:00:00 - 12:00:00
                        "closes" => fake()->numberBetween(86400 / 2 + 1, 86400), // 12:00:01 - 24:00:00
                    ]);
            }
        });
    }
}
