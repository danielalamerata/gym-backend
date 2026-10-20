<?php

namespace App\Console\Commands;

use App\Cobros\PoliticaDeCobro;
use App\Models\Pago;
use App\Models\Socio;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerarPagos extends Command
{
    protected $signature = 'pagos:generar
        {--mes= : Mes a generar en formato AAAA-MM (por defecto, el mes actual)}';

    protected $description = 'Crea el pago pendiente del mes para cada socio activo';

    public function handle(PoliticaDeCobro $politica): int
    {
        $mes = $this->mes();

        if ($mes === null) {
            $this->error('El mes tiene que tener el formato AAAA-MM, por ejemplo 2026-11.');

            return self::FAILURE;
        }

        // los que ya tienen el pago de este mes se saltean
        $conPago = Pago::whereDate('mes_abonado', $mes)->pluck('socio_id')->flip();

        // no se cobra un mes anterior a la fecha de inicio del socio
        $socios = Socio::with(['plan', 'user'])
            ->where('estado', 'activo')
            ->whereDate('fecha_inicio', '<=', $mes->endOfMonth())
            ->orderBy('id')
            ->get();

        $creados = 0;
        $salteados = 0;
        $fallidos = [];

        foreach ($socios as $socio) {
            if ($conPago->has($socio->id)) {
                $salteados++;

                continue;
            }

            try {
                $cobro = $politica->calcular($socio, $mes);

                Pago::create([
                    'socio_id' => $socio->id,
                    'plan_id' => $socio->plan_id,
                    'mes_abonado' => $mes,
                    'monto_original' => $cobro->monto,
                    'fecha_vencimiento' => $cobro->fechaVencimiento,
                    'estado' => 'pendiente',
                ]);

                $creados++;
            } catch (UniqueConstraintViolationException) {
                // otra corrida lo creó mientras tanto
                $salteados++;
            } catch (Throwable $e) {
                $fallidos[] = [$socio->id, "{$socio->user->nombre} {$socio->user->apellido}", $e->getMessage()];
                Log::error('pagos:generar no pudo crear el pago', [
                    'socio_id' => $socio->id,
                    'mes' => $mes->format('Y-m'),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Mes {$mes->format('Y-m')}: {$creados} creados, {$salteados} salteados, ".count($fallidos).' fallidos.');

        if ($fallidos !== []) {
            $this->table(['Socio', 'Nombre', 'Error'], $fallidos);
        }

        return $fallidos === [] ? self::SUCCESS : self::FAILURE;
    }

    // día 1 del mes pedido, o null si --mes no tiene el formato AAAA-MM
    private function mes(): ?CarbonImmutable
    {
        $opcion = $this->option('mes');

        if ($opcion === null) {
            return CarbonImmutable::today()->startOfMonth();
        }

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $opcion)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m', $opcion);
    }
}
