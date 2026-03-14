<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        return response()->json(Categoria::all());
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|string|unique:categorias,nombre']);
        $categoria = Categoria::create($request->all());
        return response()->json($categoria, 201);
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
