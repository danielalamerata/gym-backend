<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Socio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PagoController extends Controller
{
    // Datos que se devuelven de cada relación (nunca la contraseña ni el token)
    private const CON_RELACIONES = [
        'socio:id,user_id,estado',
        'socio.user:id,nombre,apellido,dni',
        'plan:id,nombre',
    ];

    /**
     * Listar pagos
     *
     * Los filtros son opcionales y se pueden combinar. Ordena del mes más nuevo al más viejo.
     * `estado=pendiente` incluye los vencidos; `estado=vencido` trae solo los pendientes con vencimiento anterior a hoy.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            /** Mes abonado, formato AAAA-MM. */
            'mes' => 'nullable|date_format:Y-m',
            'estado' => 'nullable|in:pendiente,pagado,vencido',
            /** Tiene que ser un socio existente. */
            'socio_id' => 'nullable|integer|exists:socios,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $filtros = $validator->validated();

        $pagos = Pago::with(self::CON_RELACIONES)
            ->when($filtros['mes'] ?? null, fn ($query, $mes) => $query->where('mes_abonado', "{$mes}-01"))
            ->when($filtros['estado'] ?? null, fn ($query, $estado) => $estado === 'vencido'
                ? $query->vencidos()
                : $query->where('estado', $estado))
            ->when($filtros['socio_id'] ?? null, fn ($query, $socioId) => $query->where('socio_id', $socioId))
            ->orderByDesc('mes_abonado')
            ->orderBy('id')
            ->get();

        return response()->json([
            'pagos' => $pagos,
        ]);
    }

    /**
     * Listar pagos pendientes
     *
     * Todos los pagos sin cobrar, de cualquier mes, del más viejo al más nuevo. Incluye los de socios dados de baja:
     * cada pago trae `socio.estado` y `vencido` para que el admin vea qué deudas son de meses anteriores.
     */
    public function pendientes()
    {
        return response()->json([
            'pagos' => Pago::with(self::CON_RELACIONES)
                ->where('estado', 'pendiente')
                ->orderBy('mes_abonado')
                ->orderBy('fecha_vencimiento')
                ->orderBy('id')
                ->get(),
        ]);
    }

    /**
     * Historial de pagos de un socio
     *
     * Todos los pagos del socio, pendientes y cobrados, del mes más nuevo al más viejo. Incluye a los socios dados de baja.
     */
    public function historialSocio(Socio $socio)
    {
        return response()->json([
            'socio' => $socio->load([
                'user:id,nombre,apellido,dni',
                'plan:id,nombre',
            ]),
            'pagos' => $socio->pagos()
                ->with('plan:id,nombre')
                ->orderByDesc('mes_abonado')
                ->get(),
        ]);
    }

    /**
     * Cobrar un pago
     *
     * Registra el cobro de un pago pendiente. No permite cambiar el monto, el plan ni el mes.
     * Si el pago ya está cobrado (o otro admin lo cobró al mismo tiempo), responde 409.
     */
    public function cobrar(Request $request, Pago $pago)
    {
        $validator = Validator::make($request->all(), [
            'metodo_pago' => 'required|in:efectivo,transferencia,debito,credito,mercado_pago',
            /** Mora que carga el admin al cobrar. Si no se manda, el pago queda sin mora. */
            'monto_mora' => 'nullable|numeric|decimal:0,2|min:0|max:99999999.99',
            /** Formato AAAA-MM-DD. No puede ser futura. Si no se manda, se usa la fecha del día. */
            'fecha_pago' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            /** Si no se manda, se conservan las notas que ya tenía el pago. */
            'notas' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();

        // solo actualiza si sigue pendiente: si dos admins cobran a la vez, el segundo actualiza 0 filas
        $cobrado = Pago::whereKey($pago->id)
            ->where('estado', 'pendiente')
            ->update([
                'estado' => 'pagado',
                'metodo_pago' => $datos['metodo_pago'],
                'monto_mora' => $datos['monto_mora'] ?? null,
                'fecha_pago' => $datos['fecha_pago'] ?? today()->toDateString(),
                ...(array_key_exists('notas', $datos) ? ['notas' => $datos['notas']] : []),
            ]);

        if ($cobrado === 0) {
            return response()->json([
                'message' => 'El pago ya está cobrado.',
            ], 409);
        }

        return response()->json([
            'message' => 'Pago cobrado correctamente',
            'pago' => $pago->fresh(self::CON_RELACIONES),
        ]);
    }

    /**
     * Descargar el recibo de un pago
     *
     * Devuelve el recibo en PDF (A4) de un pago cobrado, para descargar. Si el pago está pendiente, responde 409.
     * Los datos del gimnasio salen de la configuración (`GIMNASIO_*` en el .env); el nombre es obligatorio.
     */
    public function recibo(Pago $pago)
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
