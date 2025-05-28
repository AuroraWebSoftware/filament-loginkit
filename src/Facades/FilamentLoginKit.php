<?php

namespace VendorName\Skeleton\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \VendorName\Skeleton\FilamentLoginKit
 */
class Skeleton extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \VendorName\Skeleton\FilamentLoginKit::class;
    }
}
