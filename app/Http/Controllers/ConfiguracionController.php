<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfiguracionController extends Controller
{
    /**
     * Ver la configuración
     *
     * Si el admin todavía no guardó un día de vencimiento, devuelve el valor por defecto (10).
     */
    public function show()
    {
        return response()->json([
            'configuracion' => $this->configuracion(),
        ]);
    }

    /**
     * Modificar la configuración
     *
     * Exige la configuración completa. Cambiar el día no afecta a los pagos ya generados: cada pago guarda su propia `fecha_vencimiento`.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            /** Día del mes hasta el que se puede pagar sin mora. De 1 a 28, para que exista en todos los meses. */
            'dia_vencimiento' => 'required|integer|between:1,28',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        Configuracion::actualizarDiaVencimiento((int) $validator->validated()['dia_vencimiento']);

        return response()->json([
            'message' => 'Configuración actualizada correctamente',
            'configuracion' => $this->configuracion(),
        ]);
    }

    private function configuracion(): array
    {
        return [
            'dia_vencimiento' => Configuracion::diaVencimiento(),
        ];
    }
}
