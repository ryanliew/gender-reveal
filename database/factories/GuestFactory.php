<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    /**
     * @return array{device_uuid: string, name: string, avatar: string, gender: Gender}
     */
    public function definition(): array
    {
        return [
            'device_uuid' => fake()->uuid(),
            'name' => fake()->firstName(),
            'avatar' => fake()->randomElement(Guest::AVATARS),
            'gender' => fake()->randomElement(Gender::cases()),
        ];
    }
}
