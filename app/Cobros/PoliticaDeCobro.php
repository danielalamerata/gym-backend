<?php

namespace App\Cobros;

use App\Models\Socio;
use Carbon\CarbonImmutable;

// cómo cobra el gimnasio; la implementación se elige en AppServiceProvider
interface PoliticaDeCobro
{
    // $socio viene con el plan cargado; $mes es el día 1 del mes que se genera
    public function calcular(Socio $socio, CarbonImmutable $mes): DatosDeCobro;
}
