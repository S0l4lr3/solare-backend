<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\ImagenProducto;


class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::with(['categoria']);

        // Filtro por búsqueda (nombre o descripción)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        // Filtro por categoría (usando el ID como lo tenías)
        if ($request->has('categoria_id') && $request->categoria_id != '') {
            $query->where('categoria_id', $request->categoria_id);
        }

        // NUEVO: Filtro por TIPO (Nombre de la categoría enviado desde tu vista)
        if ($request->has('tipo') && $request->tipo !== 'TODOS') {
            $query->whereHas('categoria', function($q) use ($request) {
                $q->where('nombre', $request->tipo);
            });
        }

        // Filtro por estado activo (opcional)
        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }

        $productos = $query->latest('id')->get();

        // Transformamos la colección para que la imagen se muestre correctamente
        $productos->each(function($producto) {
            // Buscamos la imagen principal en la tabla imagenes_producto
            $imagenPrincipal = ImagenProducto::where('producto_id', $producto->id)
                ->where('es_principal', 1)
                ->first();

            if ($imagenPrincipal) {
                // Si existe, la asignamos a la propiedad imagen_url
                $producto->imagen_url = $imagenPrincipal->url;
            } else {
                // Si no existe, dejamos null o un valor por defecto
                $producto->imagen_url = null;
            }
        });

        return response()->json($productos);
    }

    
    /**
     * Crear un nuevo producto con imagen y stock.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'precio_base' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $producto = new Producto($request->except('imagen'));

        if (!$request->sku_base) {
            $producto->sku_base = strtoupper(Str::random(10));
        }

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('productos', 'public');
            $producto->imagen_url = $path;
            
            // Creamos o actualizamos el registro en la tabla imagenes_producto
            \App\Models\ImagenProducto::updateOrCreate(
                ['producto_id' => $producto->id, 'es_principal' => 1],
                ['url' => $path, 'orden' => 0]
            );
        }

        $producto->save();

        return response()->json(['message' => 'Producto creado con éxito', 'producto' => $producto], 201);
    }

    /**
     * Ver un producto específico.
     */
    public function show($id)
    {
        $producto = Producto::with('categoria')->findOrFail($id);

        // Buscamos la imagen principal en la tabla imagenes_producto
        $imagenPrincipal = ImagenProducto::where('producto_id', $producto->id)
            ->where('es_principal', 1)
            ->first();

        if ($imagenPrincipal) {
            // Si existe, la asignamos a la propiedad imagen_url
            $producto->imagen_url = $imagenPrincipal->url;
        } else {
            // Si no existe, dejamos null o un valor por defecto
            $producto->imagen_url = null;
        }
        $imagenes = ImagenProducto::where('producto_id', $producto->id)->get();
        $producto->imagenes = $imagenes;

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
            if ($producto->imagen_url) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($producto->imagen_url);
            }
            $path = $request->file('imagen')->store('productos', 'public');
            $producto->imagen_url = $path;
            
            // Sincronizamos con la tabla imagenes_producto como imagen principal
            \App\Models\ImagenProducto::updateOrCreate(
                ['producto_id' => $producto->id, 'es_principal' => 1],
                ['url' => $path, 'orden' => 0]
            );
        }

        $producto->save();

        return response()->json(['message' => 'Producto actualizado', 'producto' => $producto]);
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

    //**
    // Mostrar imagen de un producto.
    //**/
    public function showproductimage($id)
    {
        $producto = Producto::findOrFail($id);
        return response()->json($producto->imagen_url);
    }
}
