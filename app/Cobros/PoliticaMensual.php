<?php

namespace App\Cobros;

use App\Models\Configuracion;
use App\Models\Socio;
use Carbon\CarbonImmutable;

// mes completo al precio actual del plan, vence el día configurado por el admin
// o la fecha de inicio del socio, la que sea más tarde
final class PoliticaMensual implements PoliticaDeCobro
{
    // se lee una sola vez por corrida, así todos los pagos vencen el mismo día
    private ?int $diaVencimiento = null;

    public function calcular(Socio $socio, CarbonImmutable $mes): DatosDeCobro
    {
        $this->diaVencimiento ??= Configuracion::diaVencimiento();

        // nadie recibe un pago que nace vencido ni que vence antes de empezar
        $vencimiento = $mes->setDay($this->diaVencimiento)
            ->max(CarbonImmutable::instance($socio->fecha_inicio));

        return new DatosDeCobro(
            monto: $socio->plan->precio,
            fechaVencimiento: $vencimiento,
        );
    }
}
