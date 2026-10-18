<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    /**
     * Listar planes
     *
     * Si el usuario es admin devuelve todos los planes, incluidos los inactivos. Para el resto, solo los activos.
     */
    public function index(Request $request)
    {
        $query = Plan::orderBy('id');

        if ($request->user()->rol !== 'admin') {
            $query->where('activo', true);
        }

        return response()->json([
            'planes' => $query->get(),
        ]);
    }

    /**
     * Crear un plan
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan = Plan::create($validator->validated())->refresh();

        return response()->json([
            'message' => 'Plan creado correctamente',
            'plan' => $plan,
        ], 201);
    }

    /**
     * Modificar un plan
     *
     * Exige el plan completo: `nombre` y `precio` son obligatorios aunque no cambien.
     * Con `activo: true` se reactiva un plan dado de baja.
     */
    public function update(Request $request, Plan $plan)
    {
        $validator = Validator::make($request->all(), $this->rules() + [
            /** `false` lo da de baja, `true` lo reactiva. Si no se manda, no cambia. */
            'activo' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan->update($validator->validated());

        return response()->json([
            'message' => 'Plan actualizado correctamente',
            'plan' => $plan,
        ]);
    }

    /**
     * Dar de baja un plan
     *
     * Baja lógica: pone `activo = false` y no borra el registro. El plan deja de aparecer en el listado para los que no son admin.
     * Si el plan tiene socios activos no se da de baja y responde 409.
     */
    public function destroy(Plan $plan)
    {
        $sociosActivos = $plan->socios()->where('estado', 'activo')->count();

        if ($sociosActivos > 0) {
            return response()->json([
                'message' => $sociosActivos === 1
                    ? 'No se puede dar de baja el plan: tiene 1 socio activo.'
                    : "No se puede dar de baja el plan: tiene {$sociosActivos} socios activos.",
                'socios_activos' => $sociosActivos,
            ], 409);
        }

        $plan->update(['activo' => false]);

        return response()->noContent();
    }

    private function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            /** Clases por semana. `null` = pase libre. */
            'frecuencia_semanal' => 'nullable|integer|min:1|max:255',
            'precio' => 'required|numeric|min:0',
        ];
    }
}
