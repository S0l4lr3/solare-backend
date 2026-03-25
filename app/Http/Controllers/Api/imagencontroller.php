<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImagenProducto;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class imagencontroller extends Controller
{
    /**
     * Listar todas las imágenes de un producto específico.
     */
    public function indexByProducto($producto_id)
    {
        $imagenes = ImagenProducto::where('producto_id', $producto_id)
            ->orderBy('orden', 'asc')
            ->get();

        return response()->json([
            'mensaje' => 'Imágenes obtenidas con éxito',
            'data' => $imagenes
        ], 200);
    }

    /**
     * Guardar una o varias imágenes nuevas para un producto.
     */
    public function store(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'es_principal' => 'nullable|boolean'
        ]);

        $producto = Producto::findOrFail($request->producto_id);
        $nuevasImagenes = [];

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $file) {
                // Si es principal, quitamos la marca de principal a las anteriores
                if ($request->es_principal) {
                    ImagenProducto::where('producto_id', $producto->id)
                        ->update(['es_principal' => 0]);
                }

                $path = $file->store('productos', 'public');
                
                $imagen = ImagenProducto::create([
                    'producto_id' => $producto->id,
                    'url' => $path,
                    'es_principal' => $request->es_principal ?? 0,
                    'orden' => ImagenProducto::where('producto_id', $producto->id)->count() + 1
                ]);

                // Sincronizar con la tabla productos si es principal
                if ($imagen->es_principal) {
                    $producto->update(['imagen_url' => $path]);
                }

                $nuevasImagenes[] = $imagen;
            }
        }

        return response()->json([
            'mensaje' => 'Imagen(es) creada(s) con éxito',
            'data' => $nuevasImagenes
        ], 201);
    }

    /**
     * Mostrar una imagen específica.
     */
    public function show($id)
    {
        $imagen = ImagenProducto::findOrFail($id);
        return response()->json([
            'mensaje' => 'Imagen obtenida con éxito',
            'data' => $imagen
        ], 200);
    }

    /**
     * Establecer una imagen como principal.
     */
    public function setPrincipal($id)
    {
        $imagen = ImagenProducto::findOrFail($id);
        $producto = Producto::findOrFail($imagen->producto_id);

        // Quitar principal a todas las del mismo producto
        ImagenProducto::where('producto_id', $imagen->producto_id)
            ->update(['es_principal' => 0]);

        // Marcar esta como principal
        $imagen->update(['es_principal' => 1]);

        // Actualizar tabla productos para compatibilidad
        $producto->update(['imagen_url' => $imagen->url]);

        return response()->json([
            'mensaje' => 'Imagen marcada como principal',
            'data' => $imagen
        ], 200);
    }

    /**
     * Eliminar una imagen.
     */
    public function destroy($id)
    {
        $imagen = ImagenProducto::findOrFail($id);
        
        // Borrar archivo físico
        if (Storage::disk('public')->exists($imagen->url)) {
            Storage::disk('public')->delete($imagen->url);
        }

        $producto_id = $imagen->producto_id;
        $fue_principal = $imagen->es_principal;

        $imagen->delete();

        // Si borramos la principal, intentamos asignar la siguiente disponible
        if ($fue_principal) {
            $siguiente = ImagenProducto::where('producto_id', $producto_id)->first();
            if ($siguiente) {
                $siguiente->update(['es_principal' => 1]);
                Producto::where('id', $producto_id)->update(['imagen_url' => $siguiente->url]);
            } else {
                Producto::where('id', $producto_id)->update(['imagen_url' => null]);
            }
        }

        return response()->json([
            'mensaje' => 'Imagen eliminada con éxito'
        ], 200);
    }
}
