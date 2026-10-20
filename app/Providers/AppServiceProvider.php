<?php

namespace App\Providers;

use App\Cobros\PoliticaDeCobro;
use App\Cobros\PoliticaMensual;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // único lugar donde se decide cómo cobra el gimnasio
        $this->app->bind(PoliticaDeCobro::class, function () {
            $politica = config('cobros.politica');

            return match ($politica) {
                'mensual' => new PoliticaMensual,
                default => throw new InvalidArgumentException(
                    "Política de cobro desconocida: {$politica}. Revisá COBROS_POLITICA en el .env."
                ),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
