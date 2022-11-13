<?php

declare(strict_types=1);

namespace App\Enum;

enum WeightUnit: string
{
    case kg = 'kg';
    case g = 'g';

    public function grams(): int
    {
        return match ($this) {
            self::kg => 1000,
            self::g => 1,
        };
    }
}