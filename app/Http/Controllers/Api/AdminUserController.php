<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * Listar todos los usuarios/empleados.
     */
    public function index()
    {
        return response()->json(Usuario::with('rol')->get());
    }

    /**
     * Crear un nuevo empleado con un rol específico.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|string|min:8',
            'rol_id' => 'required|exists:roles,id',
            'activo' => 'boolean'
        ]);

        $usuario = Usuario::create([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol_id' => $request->rol_id,
            'activo' => $request->activo ?? true,
            'creado_en' => now(),
            'actualizado_en' => now()
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'usuario' => $usuario->load('rol')
        ], 201);
    }

    /**
     * Actualizar datos de un empleado.
     */
    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        $request->validate([
            'email' => 'email|unique:usuarios,email,' . $id,
            'rol_id' => 'exists:roles,id'
        ]);

        if ($request->has('password')) {
            $request->merge(['password' => Hash::make($request->password)]);
        }

        $usuario->update($request->all());

        return response()->json(['message' => 'Usuario actualizado', 'usuario' => $usuario]);
    }

    /**
     * Desactivar un usuario (Baja lógica).
     */
    public function destroy($id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->update(['activo' => false]);
        return response()->json(['message' => 'Usuario desactivado']);
    }
}
