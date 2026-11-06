<?php

namespace App\Http\Controllers;

use App\Recibos\GeneradorDeRecibo;
use Illuminate\Http\Request;

class MisPagosController extends Controller
{
    /**
     * Listar mis pagos
     *
     * Los pagos del socio logueado, pendientes y cobrados, del mes más nuevo al más viejo.
     * Si el usuario no tiene socio (por ejemplo, un admin), responde 403.
     */
    public function index(Request $request)
    {
        $socio = $request->user()->socio;

        if (! $socio) {
            return $this->soloSocios();
        }

        return response()->json([
            // las notas las escribe el admin al cobrar: son internas
            'pagos' => $socio->pagos()
                ->with('plan:id,nombre')
                ->orderByDesc('mes_abonado')
                ->get()
                ->makeHidden('notas'),
        ]);
    }

    /**
     * Descargar el recibo de uno de mis pagos
     *
     * Devuelve el recibo en PDF de un pago cobrado del socio logueado. Si está pendiente, responde 409.
     * Si el pago no existe o es de otro socio, responde 404. Si el usuario no tiene socio, responde 403.
     */
    public function recibo(Request $request, GeneradorDeRecibo $generador, string $id)
    {
        $socio = $request->user()->socio;

        if (! $socio) {
            return $this->soloSocios();
        }

        // se busca solo entre los pagos del socio: uno ajeno responde igual que uno que no existe
        $pago = $socio->pagos()->find($id);

        if (! $pago) {
            return response()->json([
                'message' => 'Pago no encontrado.',
            ], 404);
        }

        return $generador->descargar($pago);
    }

    private function soloSocios()
    {
        return response()->json([
            'message' => 'Esta sección es solo para socios.',
        ], 403);
    }
}
