<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     * Aquí se define el limitador "login" usado en routes/api.php.
     * 5 intentos por minuto, agrupados por email + IP -> a partir del 6to intento
     * responde 429 Too Many Requests (Prueba T14).
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Producto::class, \App\Policies\ProductoPolicy::class);

        \Illuminate\Database\Eloquent\Model::preventLazyLoading(! $this->app->isProduction());

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email', '').'|'.$request->ip());
        });
    }
}
