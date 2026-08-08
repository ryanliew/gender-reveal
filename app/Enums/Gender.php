<?php

namespace App\Enums;

enum Gender: string
{
    case Boy = 'boy';
    case Girl = 'girl';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
