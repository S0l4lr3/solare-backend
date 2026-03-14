<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductoController extends Controller
{
    /**
     * Listar productos con filtros y búsqueda.
     */
    public function index(Request $request)
    {
        $query = Producto::query();

        // Filtro por búsqueda (nombre o descripción)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        // Filtro por categoría
        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Filtro por precio (rango)
        if ($request->has('min_price')) {
            $query->where('precio_base', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('precio_base', '<=', $request->max_price);
        }

        $productos = $query->with('categoria')->get();

        return response()->json($productos);
    }

    /**
     * Crear un nuevo producto con imagen.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'precio_base' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sku_base' => 'nullable|string|unique:productos,sku_base'
        ]);

        $producto = new Producto($request->except('imagen'));

        // Si no hay SKU, lo generamos
        if (!$request->sku_base) {
            $producto->sku_base = strtoupper(Str::random(10));
        }

        // Subida de imagen
        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('productos', 'public');
            $producto->imagen_url = $path;
        }

        $producto->save();

        return response()->json([
            'message' => 'Producto creado con éxito',
            'producto' => $producto
        ], 201);
    }

    /**
     * Ver un producto específico.
     */
    public function show($id)
    {
        $producto = Producto::with('categoria')->findOrFail($id);
        return response()->json($producto);
    }

    /**
     * Actualizar un producto.
     */
    public function update(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);

        $request->validate([
            'nombre' => 'string|max:150',
            'precio_base' => 'numeric|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $producto->fill($request->except('imagen'));

        if ($request->hasFile('imagen')) {
            // Borrar imagen anterior si existe
            if ($producto->imagen_url) {
                Storage::disk('public')->delete($producto->imagen_url);
            }
            $path = $request->file('imagen')->store('productos', 'public');
            $producto->imagen_url = $path;
        }

        $producto->save();

        return response()->json([
            'message' => 'Producto actualizado con éxito',
            'producto' => $producto
        ]);
    }

    /**
     * Eliminar un producto.
     */
    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        
        if ($producto->imagen_url) {
            Storage::disk('public')->delete($producto->imagen_url);
        }

        $producto->delete();

        return response()->json(['message' => 'Producto eliminado']);
    }
}
