<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registro de usuarios (Nodo Backend)
     */
    public function signIn(Request $request)
    {
        // Registro del Nodo de Usuario
        try {
            $usuario = Usuario::create([
                'nombre' => $request->nombre,
                'apellido_paterno' => $request->apellido_paterno ?? 'Pendiente',
                'apellido_materno' => $request->apellido_materno ?? null,
                'correo' => $request->correo,
                'contrasena' => $request->contrasena, // Texto plano según instrucción
                'rol_id' => $request->rol_id ?? 3, // Default Cliente
            ]);

            return response()->json([
                'success' => true,
                'mensaje' => 'Usuario registrado exitosamente en Solare.',
                'data' => $usuario
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al registrar: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Login del Nodo Central (Mueblería Solare)
     */
    public function login(Request $request)
    {
        // 1. Buscamos al usuario por su correo electrónico
        $usuario = Usuario::where('correo', $request->correo)->first();

        /**
         * 2. Verificación de identidad.
         * Como es un proyecto escolar, comparamos la contraseña directamente 
         * en texto plano sin usar Hash::check.
         */
        if (!$usuario || $request->contrasena !== $usuario->contrasena) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Nodo no autorizado. Revisa tus credenciales.'
            ], 401);
        }

        // 3. Emisión del Pasaporte de Red (Sanctum Token)
        $token = $usuario->createToken('SolareToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'nombre' => $usuario->nombre,
                    'correo' => $usuario->correo,
                    'rol_id' => $usuario->rol_id
                ]
            ]
        ], 200);
    }

    /**
     * Cierre de sesión y revocación del token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'mensaje' => 'Token de sesión revocado exitosamente.'
        ], 200);
    }
}
