<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->routeIs('filament.*.account')
            || $request->routeIs('filament.*.auth.logout')
            || $request->routeIs('filament.admin.pages.role-switch')
        ) {
            return $next($request);
        }

        $user = Filament::auth()->user();

        if (!$user) {
            return $next($request);
        }

        if ($request->session()->get('login_type') === 'sms') {
            return $next($request);
        }

        if (
            $user->is_2fa_required &&
            (
                empty($user->two_factor_type) ||
                is_null($user->two_factor_type) ||
                is_null($user->two_factor_confirmed_at)
            )
        ) {
            $panel = Filament::getCurrentPanel();

            if ($panel) {
                $basePath = rtrim('/' . ltrim($panel->getPath(), '/'), '/');

                if ($tenant = Filament::getTenant()) {
                    return redirect("$basePath/$tenant/account");
                }

                return redirect("$basePath/account");
            }
        }

        return $next($request);
    }
}
