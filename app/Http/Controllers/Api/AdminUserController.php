<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rol;
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

    //usuarios por id
    public function show($id)
    {
       $usuario = Usuario::with('rol')->findOrFail($id);
       $roles = Rol::all();
       return response()->json([
        'usuario' => $usuario,
        'roles' => $roles
       ], 200);
    }

    /**
     * Crear un nuevo empleado con un rol específico.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'correo' => 'required|email|unique:users,correo',
            'contrasena' => 'required|string|min:4', // Cambiado de password a contrasena
            'rol_id' => 'required|exists:roles,id'
        ]);

        $usuario = Usuario::create([
            'nombre' => $request->nombre,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'correo' => $request->correo,
            'contrasena' => $request->contrasena, // Sin encriptar como solicitaste
            'rol_id' => $request->rol_id,
            'creado_en' => now(),
            'actualizado_en' => now()
        ]);

        return response()->json([
            'message' => 'Usuario administrativo creado exitosamente en la red Solare',
            'usuario' => $usuario->load('rol')
        ], 201);
    }

    /**
     * Actualizar datos de un empleado (Unificado con la tabla 'users').
     */
    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        $request->validate([
            'correo' => 'email|unique:users,correo,' . $id,
            'rol_id' => 'exists:roles,id'
        ]);

        $datos = $request->only(['nombre', 'apellido_paterno', 'apellido_materno', 'correo', 'rol_id']);
        
        if ($request->filled('contrasena')) {
            $datos['contrasena'] = $request->contrasena;
        }

        $usuario->update($datos);

        return response()->json([
            'message' => 'Datos de usuario sincronizados correctamente',
            'usuario' => $usuario->load('rol')
        ]);
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

    public function editarusuarios(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'correo' => 'required|email|unique:users,correo,' . $id,
            'rol_id' => 'required|exists:roles,id',
        ]);

        $datos = [
            'nombre' => $request->nombre,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'correo' => $request->correo,
            'rol_id' => $request->rol_id
        ];
        if($request -> filled ('contrasena')){
            $datos['contrasena'] = $request->contrasena;
        }
        $usuario->update($datos);
        return response()->json(['message' => 'Usuario actualizado', 'usuario' => $usuario->load('rol')], 200);


    }

    public function encontrarusuarios($id)
    {
        $usuario = Usuario::findOrFail($id)->with('rol');
        return response()->json(['usuario' => $usuario]);
    }
}
