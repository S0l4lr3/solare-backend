<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function signIn(Request $request){
        // Validamos lo que viene de la "maleta" $request
    $request->validate([
        'nombre' => 'required|string',
        'correo' => 'required|email|unique:usuarios,correo',
        'contrasena' => 'required|min:8',
        'rol_id' => 'required|integer'
    ]);

    return Usuario::create([
        'nombre' => $request->nombre,
        'apellido_paterno' => $request->apellido_paterno,
        'apellido_materno' => $request->apellido_materno,
        'correo' => $request->correo,
        'contrasena' => bcrypt($request->contrasena), // Encriptamos
        'rol_id' => $request->rol_id,
    ]);
    }
 // En app/Http/Controllers/AuthController.php

public function login(Request $request)
{
    // Buscamos al usuario por correo
    $usuario = Usuario::where('correo', $request->correo)->first();

    // Verificamos existencia y contraseña (usando el helper Hash o la fachada)
    if (!$usuario || !\Illuminate\Support\Facades\Hash::check($request->contrasena, $usuario->contrasena)) {
        return response()->json([
            'error' => 'No autorizado',
            'mensaje' => 'Revisa que tu correo y contraseña sean correctos.'
        ], 401);
    }

    // Ahora que getAuthIdentifier() devuelve el ID, esto funcionará perfecto
    $token = $usuario->createToken('SolareToken')->accessToken;

    return response()->json([
        'access_token' => $token,
        'user' => [
            'nombre' => $usuario->nombre,
            'correo' => $usuario->correo
        ]
    ], 200);
}
}
