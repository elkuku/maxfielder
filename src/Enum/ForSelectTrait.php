<?php

declare(strict_types=1);

namespace App\Enum;

trait ForSelectTrait
{
    /**
     * @return array<string, string>
     */
    public static function forSelect(): array
    {
        return array_column(self::cases(), 'name', 'value');
    }
}
