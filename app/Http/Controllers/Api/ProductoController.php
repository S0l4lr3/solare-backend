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

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('categoria_id') && $request->categoria_id != '') {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->has('tipo') && $request->tipo !== 'TODOS') {
            $query->whereHas('categoria', function ($q) use ($request) {
                $q->where('nombre', $request->tipo);
            });
        }

        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }

        $productos = $query->latest('id')->get();

        $productos->each(function ($producto) {
            $imagenPrincipal = ImagenProducto::where('producto_id', $producto->id)
                ->where('es_principal', 1)
                ->first();

            if ($imagenPrincipal) {
                $producto->imagen_url = $imagenPrincipal->url;
            } else {
                $producto->imagen_url = null;
            }
        });

        return response()->json($productos);
    }

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

        $producto->save(); // ← primero guardamos para tener el id

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('productos', 'public');
            $producto->imagen_url = $path;
            $producto->save(); // actualizamos con la url

            \App\Models\ImagenProducto::updateOrCreate(
                ['producto_id' => $producto->id, 'es_principal' => 1],
                ['url' => $path, 'orden' => 0]
            );
        }

        return response()->json(['message' => 'Producto creado con éxito', 'producto' => $producto], 201);
    }

    public function show($id)
    {
        $producto = Producto::with('categoria')->findOrFail($id);

        $imagenPrincipal = ImagenProducto::where('producto_id', $producto->id)
            ->where('es_principal', 1)
            ->first();

        if ($imagenPrincipal) {
            $producto->imagen_url = $imagenPrincipal->url;
        } else {
            $producto->imagen_url = null;
        }
        $imagenes = ImagenProducto::where('producto_id', $producto->id)->get();
        $producto->imagenes = $imagenes;

        return response()->json($producto);
    }

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

            \App\Models\ImagenProducto::updateOrCreate(
                ['producto_id' => $producto->id, 'es_principal' => 1],
                ['url' => $path, 'orden' => 0]
            );
        }

        $producto->save();

        return response()->json(['message' => 'Producto actualizado', 'producto' => $producto]);
    }

    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        if ($producto->imagen_url) {
            Storage::disk('public')->delete($producto->imagen_url);
        }
        $producto->delete();
        return response()->json(['message' => 'Producto eliminado']);
    }

    public function showproductimage($id)
    {
        $producto = Producto::findOrFail($id);
        return response()->json($producto->imagen_url);
    }
}
