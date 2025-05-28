<?php

namespace AuroraWebSoftware\FilamentLoginKit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AuroraWebSoftware\FilamentLoginKit\FilamentLoginKit
 */
class FilamentLoginKit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FilamentLoginKit::class;
    }
}
