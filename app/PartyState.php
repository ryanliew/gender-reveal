<?php

namespace App;

use App\Enums\Gender;
use App\Models\Guest;
use Illuminate\Support\Facades\Cache;

class PartyState
{
    private const string REVEAL_KEY = 'party.revealed_gender';

    public static function revealedGender(): ?Gender
    {
        $value = Cache::get(self::REVEAL_KEY);

        return $value === null ? null : Gender::from($value);
    }

    public static function reveal(Gender $gender): void
    {
        Cache::forever(self::REVEAL_KEY, $gender->value);
    }

    public static function reset(): void
    {
        Cache::forget(self::REVEAL_KEY);
        Guest::query()->delete();
    }
}
