<?php

namespace AuroraWebSoftware\FilamentLoginKit\Traits;

trait EnumNamesTrait
{
    /**
     * Get the names of the enum.
     *
     * @return array<string>
     */
    public static function names(): array
    {
        return array_map(fn ($enum) => $enum->name, static::cases());
    }
}
