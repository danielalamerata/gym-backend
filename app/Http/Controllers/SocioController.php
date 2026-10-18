<?php

namespace App\Http\Controllers;

use App\Models\Socio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SocioController extends Controller
{
    // Datos que se devuelven de cada relación (nunca la contraseña ni el token)
    private const CON_RELACIONES = [
        'user:id,nombre,apellido,dni,email,telefono,direccion',
        'plan:id,nombre,frecuencia_semanal,precio,activo',
    ];

    /**
     * Listar socios
     *
     * Devuelve todos los socios, activos e inactivos, con sus datos personales y su plan.
     */
    public function index()
    {
        return response()->json([
            'socios' => Socio::with(self::CON_RELACIONES)->orderBy('id')->get(),
        ]);
    }

    /**
     * Ver un socio
     */
    public function show(Socio $socio)
    {
        return response()->json([
            'socio' => $socio->load(self::CON_RELACIONES),
        ]);
    }

    /**
     * Modificar un socio
     *
     * Exige el socio completo: datos personales (se guardan en users) y membresía (se guardan en socios).
     * Con `estado: "inactivo"` se da de baja y se revocan sus tokens. Con `estado: "activo"` se reactiva.
     */
    public function update(Request $request, Socio $socio)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            /** Único: no puede haber dos usuarios con el mismo DNI. */
            'dni' => ['required', 'string', Rule::unique('users', 'dni')->ignore($socio->user_id)],
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            /** Único: no puede haber dos usuarios con el mismo email. */
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($socio->user_id)],
            /** Tiene que ser un plan activo, salvo que sea el plan que el socio ya tiene. */
            'plan_id' => ['required', 'integer', Rule::exists('planes', 'id')->where(
                fn ($query) => $query->where('activo', true)->orWhere('id', $socio->plan_id)
            )],
            'estado' => 'required|in:activo,inactivo',
            /** Formato AAAA-MM-DD. */
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'notas' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();
        $seDaDeBaja = $socio->estado === 'activo' && $datos['estado'] === 'inactivo';

        DB::transaction(function () use ($socio, $datos, $seDaDeBaja) {
            $socio->user->update(collect($datos)->only([
                'nombre', 'apellido', 'dni', 'telefono', 'direccion', 'email',
            ])->all());

            $socio->update(collect($datos)->only([
                'plan_id', 'estado', 'fecha_inicio', 'notas',
            ])->all());

            if ($seDaDeBaja) {
                $this->revocarTokens($socio);
            }
        });

        return response()->json([
            'message' => 'Socio actualizado correctamente',
            'socio' => $socio->fresh(self::CON_RELACIONES),
        ]);
    }

    /**
     * Dar de baja un socio
     *
     * Baja lógica: pone `estado = "inactivo"` y no borra el registro. También revoca todos sus tokens, así que pierde la sesión.
     */
    public function destroy(Socio $socio)
    {
        DB::transaction(function () use ($socio) {
            $socio->update(['estado' => 'inactivo']);
            $this->revocarTokens($socio);
        });

        return response()->noContent();
    }

    // Cierra todas las sesiones del socio
    private function revocarTokens(Socio $socio): void
    {
        $socio->user->tokens()->delete();
    }
}
