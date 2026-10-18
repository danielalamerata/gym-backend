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

    // El chequeo de socios activos con este plan se agrega con el módulo Socios.
    /**
     * Dar de baja un plan
     *
     * Baja lógica: pone `activo = false` y no borra el registro. El plan deja de aparecer en el listado para los que no son admin.
     */
    public function destroy(Plan $plan)
    {
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
