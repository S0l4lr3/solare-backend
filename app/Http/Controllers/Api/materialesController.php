<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\materiales;
use Illuminate\Http\Request;

class materialesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //traer todos los materiales
        $materiales = materiales::all();
        return response()->json([
            'mensaje' => 'Materiales obtenidos con éxito',
            'data' => $materiales
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //guardar un material
        $materiales = materiales::create($request->all());
        return response()->json([
            'mensaje' => 'Material creado con éxito',
            'data' => $materiales
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(materiales $materiales)
    {
        //mostrar un material
        return response()->json([
            'mensaje' => 'Material obtenido con éxito',
            'data' => $materiales
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, materiales $materiales)
    {
        //actualizar un material
        $materiales->update($request->all());
        return response()->json([
            'mensaje' => 'Material actualizado con éxito',
            'data' => $materiales
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(materiales $materiales)
    {
        //eliminar un material
        $materiales->delete();
        return response()->json([
            'mensaje' => 'Material eliminado con éxito',
            'data' => $materiales
        ], 200);
    }
}
