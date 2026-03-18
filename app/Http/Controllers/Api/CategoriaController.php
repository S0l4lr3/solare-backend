<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        // Cargamos las categorías incluyendo el conteo de la relación 'productos'
        return response()->json(Categoria::withCount('productos')->get());
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|unique:categorias,nombre|max:100',
                'descripcion' => 'nullable|string'
            ]);

            $categoria = Categoria::create($validated);
            
            return response()->json([
                'mensaje' => 'Categoría creada con éxito',
                'data' => $categoria
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errores' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo crear la categoría',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        return response()->json(Categoria::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->update($request->all());
        return response()->json($categoria);
    }

    public function destroy($id)
    {
        Categoria::findOrFail($id)->delete();
        return response()->json(['message' => 'Categoría eliminada']);
    }
}
