<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
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

    public function update(Request $request, Plan $plan)
    {
        $validator = Validator::make($request->all(), $this->rules() + [
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

    // Baja lógica: el plan deja de estar disponible pero no se borra.
    // El chequeo de socios activos con este plan se agrega con el módulo Socios.
    public function destroy(Plan $plan)
    {
        $plan->update(['activo' => false]);

        return response()->noContent();
    }

    private function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'frecuencia_semanal' => 'nullable|integer|min:1|max:255',
            'precio' => 'required|numeric|min:0',
        ];
    }
}
