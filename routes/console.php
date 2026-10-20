<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// todos los días: el comando saltea a quien ya tiene el pago del mes,
// así los socios que entran a mitad de mes reciben el suyo esa noche
Schedule::command('pagos:generar')
    ->dailyAt('00:05')
    ->withoutOverlapping(60);
