<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImagenProducto;
use Illuminate\Http\Request;

class imagencontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $imagenProducto = ImagenProducto::all();
        return response()->json([
            'mensaje' => 'Imagenes obtenidas con éxito',
            'data' => $imagenProducto
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $imagenProducto = ImagenProducto::create($request->all());
        return response()->json([
            'mensaje' => 'Imagen creada con éxito',
            'data' => $imagenProducto
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ImagenProducto $imagenProducto)
    {
        //
        $imagenProducto = ImagenProducto::find($imagenProducto);
        return response()->json([
            'mensaje' => 'Imagen obtenida con éxito',
            'data' => $imagenProducto
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ImagenProducto $imagenProducto)
    {
        //
        $imagenProducto->update($request->all());
        return response()->json([
            'mensaje' => 'Imagen actualizada con éxito',
            'data' => $imagenProducto
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ImagenProducto $imagenProducto)
    {
        //
        $imagenProducto->delete();
        return response()->json([
            'mensaje' => 'Imagen eliminada con éxito',
            'data' => $imagenProducto
        ], 200);
    }
}
