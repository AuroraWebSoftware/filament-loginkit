<?php

namespace AuroraWebSoftware\FilamentLoginKit\Enums;

use AuroraWebSoftware\FilamentLoginKit\Traits\EnumArraySerializableTrait;
use Filament\Support\Contracts\HasLabel;

enum TwoFactorType: string implements HasLabel
{
    use EnumArraySerializableTrait;

    case authenticator = 'authenticator';

    case email = 'email';
    case sms = 'sms';

    /**
     * Get the values of the enum.
     *
     * @return array<int, string|null>
     */
    public static function values(): array
    {
        return array_map(fn ($type) => $type->getLabel(), self::cases());
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::email => __('Email'),
            self::authenticator => __('Authenticator app'),
            self::sms => __('SMS'),
        };
    }
}
