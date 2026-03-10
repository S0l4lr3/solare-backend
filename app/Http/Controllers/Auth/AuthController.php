<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use App\Models\Cliente;
use App\Models\Rol;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Registrar un nuevo cliente.
     */
    public function register(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'correo' => 'required|email|unique:usuarios,correo',
            'contrasena' => 'required|string|min:8|confirmed',
            'telefono' => 'required|string|max:20',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // 1. Buscar el ID del rol 'Cliente'
                $rolCliente = Rol::where('nombre', 'Cliente')->first();

                // 2. Crear el Usuario
                $user = User::create([
                    'nombre' => $request->nombre,
                    'apellido_paterno' => $request->apellido_paterno,
                    'apellido_materno' => $request->apellido_materno,
                    'correo' => $request->correo,
                    'contrasena' => Hash::make($request->contrasena),
                    'rol_id' => $rolCliente->id ?? 3, // Por defecto 3 si no lo encuentra
                ]);

                // 3. Crear el Perfil de Cliente vinculado
                Cliente::create([
                    'usuario_id' => $user->id,
                    'telefono' => $request->telefono,
                ]);

                // 4. Generar Token
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'message' => 'Usuario registrado exitosamente como Cliente',
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user->load('rol')
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener listado de roles (Solo Admin).
     */
    public function getRoles()
    {
        return response()->json(Rol::all());
    }

    /**
     * Crear cuenta de empleado (Solo Admin).
     */
    public function storeEmployee(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'correo' => 'required|email|unique:usuarios,correo',
            'contrasena' => 'required|string|min:8|confirmed',
            'rol_id' => 'required|exists:roles,id',
        ]);

        // Evitar que el Admin cree Clientes por esta vía
        $rolCliente = Rol::where('nombre', 'Cliente')->first();
        if ($request->rol_id == $rolCliente->id) {
            return response()->json(['message' => 'Para crear clientes use la ruta de registro pública'], 400);
        }

        $user = User::create([
            'nombre' => $request->nombre,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'correo' => $request->correo,
            'contrasena' => Hash::make($request->contrasena),
            'rol_id' => $request->rol_id,
        ]);

        return response()->json([
            'message' => 'Cuenta de personal creada exitosamente',
            'user' => $user->load('rol')
        ], 201);
    }

    /**
     * Iniciar sesión y obtener un token de acceso.
     */
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'contrasena' => 'required',
        ]);

        $user = User::with('rol')
            ->where('correo', $request->correo)
            ->first();

        if (!$user || !Hash::check($request->contrasena, $user->contrasena)) {
            throw ValidationException::withMessages([
                'correo' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Crear un token con Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellido_paterno' => $user->apellido_paterno,
                'correo' => $user->correo,
                'rol' => $user->rol->nombre
            ]
        ]);
    }

    /**
     * Cerrar sesión (revocar el token actual).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    /**
     * Obtener el perfil del usuario autenticado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        return response()->json($request->user()->load('rol'));
    }
}
