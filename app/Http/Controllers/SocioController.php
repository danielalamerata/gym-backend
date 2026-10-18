<?php

namespace App\Http\Controllers;

use App\Models\Socio;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
     * Crear un socio
     *
     * Crea el user (rol `cliente`) y su membresía. Devuelve una contraseña temporal aleatoria que se muestra solo en esta respuesta.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            /** Único: no puede haber dos usuarios con el mismo DNI. */
            'dni' => 'required|string|unique:users,dni',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            /** Único: no puede haber dos usuarios con el mismo email. */
            'email' => 'required|string|email|max:255|unique:users,email',
            /** Tiene que ser un plan activo. */
            'plan_id' => ['required', 'integer', Rule::exists('planes', 'id')->where('activo', true)],
            /** Formato AAAA-MM-DD. Si no se manda, se usa la fecha del día. */
            'fecha_inicio' => 'nullable|date_format:Y-m-d',
            'notas' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();
        $password = Str::password(12, symbols: false);

        $socio = DB::transaction(function () use ($datos, $password) {
            $user = User::create([
                'name' => "{$datos['nombre']} {$datos['apellido']}",
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'dni' => $datos['dni'],
                'telefono' => $datos['telefono'] ?? null,
                'direccion' => $datos['direccion'] ?? null,
                'email' => $datos['email'],
                'password' => $password,
                'rol' => 'cliente',
            ]);

            return $user->socio()->create([
                'plan_id' => $datos['plan_id'],
                // fecha del día en Argentina (la app guarda en UTC)
                'fecha_inicio' => $datos['fecha_inicio'] ?? now('America/Argentina/Buenos_Aires')->toDateString(),
                'notas' => $datos['notas'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Socio creado correctamente',
            'socio' => $socio->fresh(self::CON_RELACIONES),
            'password_temporal' => $password,
        ], 201);
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
     * Un socio que queda activo tiene que tener un plan activo: si su plan está dado de baja, hay que asignarle otro.
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
            /** Tiene que ser un plan activo. Solo puede conservar su plan actual dado de baja si el socio queda inactivo. */
            'plan_id' => ['required', 'integer', Rule::exists('planes', 'id')->where(
                fn ($query) => $query->where('activo', true)->orWhere('id', $socio->plan_id)
            )],
            'estado' => 'required|in:activo,inactivo',
            /** Formato AAAA-MM-DD. */
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'notas' => 'nullable|string',
        ]);

        // Ningún socio activo puede tener un plan inactivo
        $validator->after(function ($validator) use ($request) {
            if ($validator->errors()->hasAny(['plan_id', 'estado'])) {
                return;
            }

            $planActivo = Plan::where('id', $request->input('plan_id'))->value('activo');

            if ($request->input('estado') === 'activo' && ! $planActivo) {
                $validator->errors()->add(
                    'plan_id',
                    'No se puede activar un socio con un plan dado de baja. Asignale un plan activo.'
                );
            }
        });

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
