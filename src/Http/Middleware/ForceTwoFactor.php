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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        // Kullanıcı yoksa devam et
        if (!$user) {
            return $next($request);
        }

        // Eğer mevcut sayfa "account" veya "logout" ise, yönlendirme yapma
        if ($request->routeIs('filament.*.account') || $request->routeIs('filament.*.auth.logout')) {
            return $next($request);
        }

        // 2FA zorunlu ve henüz etkinleştirilmemiş mi kontrol et
        if (
            $user->is_2fa_required
            && (empty($user->two_factor_type) || is_null($user->two_factor_type))
        ){
            $panel = Filament::getCurrentPanel();

            if ($panel) {
                $routeParams = [];

                // Tenant varsa parametrelere ekle
                if ($tenant = Filament::getTenant()) {
                    $routeParams['tenant'] = $tenant;
                }

                return redirect('/admin/account');
            }
        }

        return $next($request);
    }
}
