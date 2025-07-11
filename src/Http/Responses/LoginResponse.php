<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Responses;

use Filament\Facades\Filament;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(Filament::getCurrentPanel()?->getUrl() ?? config('fortify.home'));
    }
}
