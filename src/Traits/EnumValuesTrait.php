<?php

namespace AuroraWebSoftware\FilamentLoginKit\Traits;

trait EnumValuesTrait
{
    /**
     * Get the values of the enum.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn ($enum) => $enum->value, static::cases());
    }
}
