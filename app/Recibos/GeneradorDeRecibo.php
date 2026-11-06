<?php

namespace App\Recibos;

use App\Models\Pago;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class GeneradorDeRecibo
{
    // Recibo en PDF de un pago cobrado. Quién puede pedirlo lo decide cada controller antes de llamar acá.
    public function descargar(Pago $pago): JsonResponse|Response
    {
        if ($pago->estado !== 'pagado') {
            return response()->json([
                'message' => 'El pago todavía no está cobrado: no tiene recibo.',
            ], 409);
        }

        $gimnasio = config('gimnasio');

        // sin el nombre del gimnasio el recibo no identifica a quién lo emite
        if (blank($gimnasio['nombre'])) {
            Log::error('No se puede generar el recibo: falta GIMNASIO_NOMBRE en el .env.', ['pago_id' => $pago->id]);

            return response()->json([
                'message' => 'No se pudo generar el recibo: falta configurar el nombre del gimnasio.',
            ], 500);
        }

        $numero = str_pad($pago->id, 6, '0', STR_PAD_LEFT);

        $pdf = Pdf::loadView('recibos.pago', [
            'pago' => $pago->load(['socio.user:id,nombre,apellido,dni', 'plan:id,nombre']),
            'gimnasio' => $gimnasio,
            'logo' => $this->rutaLogo($gimnasio['logo']),
            'numero' => $numero,
        ])->setPaper('a4');

        // tiene datos personales (DNI): que ningún navegador ni proxy lo guarde
        return $pdf->download("recibo-{$numero}.pdf")
            ->header('Cache-Control', 'no-store, private');
    }

    // Ruta absoluta del logo, o null si no está configurado, no existe, está fuera de su carpeta o falta gd
    private function rutaLogo(?string $logo): ?string
    {
        if (blank($logo)) {
            return null;
        }

        $carpeta = storage_path('app/private');
        $ruta = realpath("{$carpeta}/{$logo}");

        if ($ruta === false || ! str_starts_with($ruta, $carpeta.DIRECTORY_SEPARATOR)) {
            Log::warning('Logo del gimnasio no encontrado: el recibo sale sin logo.', ['GIMNASIO_LOGO' => $logo]);

            return null;
        }

        if (! extension_loaded('gd')) {
            Log::warning('Falta la extensión gd de PHP: el recibo sale sin logo.');

            return null;
        }

        return $ruta;
    }
}
