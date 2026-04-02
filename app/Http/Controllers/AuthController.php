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

        // 2. Verificación específica
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El correo electrónico no se encuentra registrado.'
            ], 401);
        }

        if ($request->contrasena !== $usuario->contrasena) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La contraseña es incorrecta.'
            ], 401);
        }

        // 3. Emisión del Pasaporte de Red (Sanctum Token)
        $token = $usuario->createToken('SolareToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $usuario // Enviamos el modelo completo
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

    /**
     * Actualizar perfil del usuario (Cliente)
     */
    public function updateProfile(Request $request)
    {
        try {
            $usuario = $request->user(); // Usuario autenticado

            // 1. Actualizamos datos personales en la tabla USERS
            $usuario->update([
                'nombre' => $request->nombre ?? $usuario->nombre,
                'apellido_paterno' => $request->apellido_paterno ?? $usuario->apellido_paterno,
                'apellido_materno' => $request->apellido_materno ?? $usuario->apellido_materno,
            ]);

            // 2. Buscamos o creamos la dirección en la tabla DIRECCION_ENVIOS
            // Usamos cliente_id para relacionarlo
            \App\Models\DireccionEnvio::updateOrCreate(
                ['cliente_id' => $usuario->id],
                [
                    'calle' => $request->calle,
                    'numero_exterior' => $request->numero_exterior,
                    'numero_interior' => $request->numero_interior,
                    'colonia' => $request->colonia,
                    'ciudad' => $request->ciudad,
                    'estado' => $request->estado,
                    'codigo_postal' => $request->codigo_postal,
                    'pais' => $request->pais ?? 'México',
                    'referencias' => $request->referencias,
                    'es_principal' => 1
                ]
            );

            return response()->json([
                'success' => true,
                'mensaje' => 'Perfil y dirección actualizados exitosamente en sus tablas correspondientes.',
                'data' => [
                    'user' => $usuario->fresh()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al actualizar: ' . $e->getMessage()
            ], 422);
        }
    }
}
