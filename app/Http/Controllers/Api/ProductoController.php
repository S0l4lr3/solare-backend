<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\ImagenProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductoController extends Controller
{
    /**
     * Listar productos con filtros y paginación.
     */
    public function index(Request $request)
    {
        $query = Producto::query();
        $perPage = $request->input('per_page', 12); // Ahora acepta un número personalizado

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }

        // Cargamos categoría e imágenes (solo la principal)
        $productos = $query->with(['categoria', 'imagenes' => function($q) {
            $q->where('es_principal', 1);
        }])->latest('id')->paginate($perPage);

        // Transformación de URLs de imagen
        $productos->getCollection()->transform(function($producto) {
            $img = $producto->imagenes->first();
            $producto->imagen_url = $img ? $img->url : null;
            $producto->full_image_url = $img ? $img->full_image_url : null;

            // Mantenemos la categoría intacta para el frontend
            return $producto;
        });

        return response()->json($productos);
    }

    /**
     * Crear un nuevo producto.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'precio_base' => 'required|numeric|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'imagen' => 'nullable|image|max:2048'
        ]);

        $producto = Producto::create($request->except('imagen'));

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('productos', 'public');
            $producto->imagen_url = $path;
            $producto->save();

            ImagenProducto::create([
                'producto_id' => $producto->id,
                'url' => $path,
                'es_principal' => 1
            ]);
        }

        return response()->json($producto, 201);
    }

    /**
     * Muestra el detalle de un producto con su stock real unificado.
     */
    public function show($id)
    {
        $producto = Producto::with(['categoria', 'imagenes', 'variantes.material'])->findOrFail($id);
        
        // Calculamos el stock total sumando las existencias de todas las variantes
        $stockTotal = $producto->variantes->sum('existencias');
        $producto->stock_real = $stockTotal;
        $producto->stock = $stockTotal; // Añadimos 'stock' para compatibilidad con el carrito del frontend
        
        // La URL de imagen principal se toma automáticamente del modelo ImagenProducto si es principal
        $imgPrincipal = $producto->imagenes->where('es_principal', 1)->first();
        $producto->full_image_url = $imgPrincipal ? $imgPrincipal->full_image_url : null;

        return response()->json($producto);
    }

    /**
     * Actualizar un producto.
     */
    public function update(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);
        $producto->update($request->except('imagen'));

        if ($request->hasFile('imagen')) {
            if ($producto->imagen_url) {
                Storage::disk('public')->delete($producto->imagen_url);
            }
            $path = $request->file('imagen')->store('productos', 'public');
            $producto->imagen_url = $path;
            $producto->save();

            ImagenProducto::updateOrCreate(
                ['producto_id' => $producto->id, 'es_principal' => 1],
                ['url' => $path]
            );
        }

        return response()->json($producto);
    }

    /**
     * Eliminar un producto.
     */
    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->delete();
        return response()->json(['message' => 'Producto eliminado']);
    }
}
