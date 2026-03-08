<?php

use Illuminate\Support\Facades\Route;

// Ruta para la raíz
Route::get('/', function () {
    return view('loginScreen.login');
})->name('loginScreen'); // Le asignamos un nombre para referenciarla

// Ruta para /login (GET) - Aquí muestras el formulario
Route::get('/login', function () {
    return view('loginScreen.login');
})->name('login'); // Nombre estándar que suele buscar Laravel

// ¡IMPORTANTE! Necesitas la ruta POST para procesar el formulario
Route::post('/login', function () {
    // Aquí irá tu lógica de autenticación más adelante
})->name('loginScreen.login');