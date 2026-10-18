<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Registrar un cliente
     *
     * Crea un usuario con rol `cliente`. El rol no se puede elegir desde este endpoint.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            /** Único: no puede haber dos usuarios con el mismo DNI. */
            'dni' => 'required|string|unique:users,dni',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            /** Único: no puede haber dos usuarios con el mismo email. */
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'dni' => $request->dni,
            'telefono' => $request->telefono,
            'direccion' => $request->direccion,
            'email' => $request->email,
            'password' => $request->password,
            'rol' => 'cliente',
        ]);

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'direccion' => $user->direccion,
                'email' => $user->email,
                'rol' => $user->rol,
            ],
        ], 201);
    }

    /**
     * Iniciar sesión
     *
     * Devuelve un token de Sanctum. Para los endpoints protegidos se manda como `Authorization: Bearer <token>`.
     * Si el usuario no existe o la contraseña no coincide, responde 422 con "Credenciales inválidas".
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            /** Email o DNI del usuario. */
            'login' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Se puede ingresar con email o con DNI
        $login = trim($request->login);
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($login)])
            ->orWhere('dni', $login)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales inválidas',
                'errors' => ['login' => ['Credenciales inválidas']],
            ], 422);
        }

        $token = $user->createToken('fitmotion')->plainTextToken;

        return response()->json([
            'message' => 'Sesión iniciada',
            'token' => $token,
            'user' => $this->userData($user),
        ]);
    }

    /**
     * Usuario autenticado
     *
     * Devuelve los datos del usuario dueño del token.
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->userData($request->user()),
        ]);
    }

    /**
     * Cerrar sesión
     *
     * Revoca solo el token usado en este pedido. Si el usuario tiene otros tokens, siguen siendo válidos.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada',
        ]);
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'nombre' => $user->nombre,
            'apellido' => $user->apellido,
            'dni' => $user->dni,
            'telefono' => $user->telefono,
            'direccion' => $user->direccion,
            'email' => $user->email,
            'rol' => $user->rol,
        ];
    }
}
