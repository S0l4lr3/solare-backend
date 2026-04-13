<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Usuario;
use App\Models\Cliente;
use App\Models\DireccionEnvio;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registro de usuarios
     */
    public function signIn(Request $request)
    {
        try {
            $usuario = Usuario::create([
                'nombre' => $request->nombre,
                'apellido_paterno' => $request->apellido_paterno ?? 'Pendiente',
                'apellido_materno' => $request->apellido_materno ?? null,
                'correo' => $request->correo,
                'contrasena' => $request->contrasena,
                'rol_id' => $request->rol_id ?? 3,
            ]);

            return response()->json(['success' => true, 'data' => $usuario], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 422);
        }
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $usuario = Usuario::where('correo', $request->correo)->first();

        if (!$usuario || $request->contrasena !== $usuario->contrasena) {
            return response()->json(['success' => false, 'mensaje' => 'Credenciales inválidas'], 401);
        }

        $token = $usuario->createToken('SolareToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => ['token' => $token, 'user' => $usuario]
        ], 200);
    }

    /**
     * Ver Perfil Completo (Con Direcciones)
     */
    public function profile(Request $request)
    {
        // 1. Cargamos el usuario con su perfil de cliente relacionado
        $usuario = Usuario::with('cliente')->find($request->user()->id);
        
        // 2. Si el perfil de cliente no existe, lo creamos para que no falle el frontend
        if (!$usuario->cliente) {
            $cliente = Cliente::create([
                'usuario_id' => $usuario->id,
                'telefono' => 'No registrado',
                'identificacion_fiscal' => null
            ]);
            $usuario = $usuario->fresh('cliente');
        }

        // 3. Obtenemos las direcciones vinculadas al perfil de cliente
        $direcciones = DireccionEnvio::where('cliente_id', $usuario->cliente->id)->get();
        
        return response()->json([
            'success' => true,
            'user' => $usuario,
            'direcciones' => $direcciones
        ]);
    }

    /**
     * Actualizar SOLO Datos Personales
     */
    public function updateProfile(Request $request)
    {
        try {
            $usuario = $request->user();
            $usuario->update($request->only(['nombre', 'apellido_paterno', 'apellido_materno', 'correo']));

            return response()->json([
                'success' => true,
                'mensaje' => 'Perfil actualizado correctamente.',
                'user' => $usuario->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 422);
        }
    }

    /**
     * Gestión de Múltiples Direcciones
     */
    public function storeAddress(Request $request)
    {
        try {
            // Buscamos el perfil de cliente del usuario autenticado
            $usuario = Usuario::with('cliente')->find($request->user()->id);
            
            if (!$usuario->cliente) {
                return response()->json(['success' => false, 'mensaje' => 'No se encontró el perfil de cliente.'], 404);
            }

            // Guardamos la dirección vinculándola al ID de la tabla 'clientes'
            $direccion = DireccionEnvio::create(array_merge($request->all(), [
                'cliente_id' => $usuario->cliente->id,
                'pais' => 'México' // Valor por defecto según Railway
            ]));

            return response()->json([
                'success' => true,
                'mensaje' => 'Nueva dirección guardada correctamente.',
                'direccion' => $direccion
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Fallo al guardar: ' . $e->getMessage()], 422);
        }
    }

    public function deleteAddress(Request $request, $id)
    {
        try {
            $usuario = Usuario::with('cliente')->find($request->user()->id);
            if (!$usuario->cliente) return response()->json(['success' => false], 404);

            DireccionEnvio::where('id', $id)
                ->where('cliente_id', $usuario->cliente->id)
                ->delete();

            return response()->json(['success' => true, 'mensaje' => 'Dirección eliminada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }
}
