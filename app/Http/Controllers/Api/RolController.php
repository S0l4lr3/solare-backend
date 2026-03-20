<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use Illuminate\Http\Request;

class RolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //traer todos los roles
        $roles = Rol::all();
        return response()->json([
            'mensaje' => 'Roles obtenidos con éxito',
            'data' => $roles
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //guardar un rol
        $rol = Rol::create($request->all());
        return response()->json([
            'mensaje' => 'Rol creado con éxito',
            'data' => $rol
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Rol $rol)
    {
        //mostrar un rol
        $rol = Rol::find($rol);
        return response()->json([
            'mensaje' => 'Rol obtenido con éxito',
            'data' => $rol
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rol $rol)
    {
        //actualizar un rol
        $rol->update($request->all());
        return response()->json([
            'mensaje' => 'Rol actualizado con éxito',
            'data' => $rol
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rol $rol)
    {
        //eliminar un rol
        $rol->delete();
        return response()->json([
            'mensaje' => 'Rol eliminado con éxito',
            'data' => $rol
        ], 200);
    }
}
