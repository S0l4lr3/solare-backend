<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illumninate\Support\Facades\Http;

class AuthController extends Controller
{
    
    public function login(){
        $url =config('app.url') . '/oauth/token';
        $response = Http::asForm()->post($url,[
                'grant_type' => 'password',
                'client_id' => env('SOLARE_ADMIN_ID'),
                'client_secret' => env('SOLARE_ADMIN_SECRET'),
                'username' => $request->correo,
                'password'=> $request->contrasena,
                'scope' => '',
        ]);
        return $response->json();
    }
}
