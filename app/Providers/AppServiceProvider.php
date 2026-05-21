<?php
namespace App\Providers;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // 5 intentos por minuto por IP — al superarlo redirige al login con error
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(fn () => back()
                    ->withInput($request->only('username'))
                    ->withErrors(['username' => 'Demasiados intentos fallidos. Espera 1 minuto e inténtalo de nuevo.'])
                );
        });
    }
}
