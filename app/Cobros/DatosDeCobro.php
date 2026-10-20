<?php

namespace App\Cobros;

use Carbon\CarbonImmutable;

// lo que una política calcula para un socio y un mes
final readonly class DatosDeCobro
{
    public function __construct(
        public string $monto,
        public CarbonImmutable $fechaVencimiento,
    ) {}
}
