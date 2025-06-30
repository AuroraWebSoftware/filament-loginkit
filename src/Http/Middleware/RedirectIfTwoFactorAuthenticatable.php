<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Middleware;

use Filament\Facades\Filament;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as Base;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticatable;

class RedirectIfTwoFactorAuthenticatable extends Base
{
    public function handle($request, $next)
    {

        if ($request->session()->get('login_type') === 'sms') {
            return $next($request);
        }

        if (Filament::getCurrentPanel()) {
            session()->put('panel', Filament::getCurrentPanel()->getId());
        }

        $user = $this->validateCredentials($request);

        if (! $user->is_2fa_required) {
            return $next($request);
        }

        $type = $user->two_factor_type;

        if ($type === 'authenticator') {
            if (
                optional($user)->two_factor_secret &&
                in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user))
            ) {
                return $this->twoFactorChallengeResponse($request, $user);
            }

            return $next($request);
        }

        if (in_array($type, ['email', 'sms'])) {
            return $this->twoFactorChallengeResponse($request, $user);
        }

        return $next($request);
    }
}
