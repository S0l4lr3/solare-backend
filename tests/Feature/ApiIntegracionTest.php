<?php

use App\Models\User;
use App\Models\Producto;
use App\Models\Categoria;

test('la api puede listar productos C46', function () {
    $response = $this->getJson('/api/productos');
    $response->assertStatus(200);
});

test('la api puede listar categorias C47', function () {
    $response = $this->getJson('/api/categorias');
    $response->assertStatus(200);
});

test('un usuario puede intentar loguearse C48', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
    
    // Verificamos que no de error de servidor (puede dar 401 si no existe el user, es correcto)
    expect($response->status())->toBeInArray([200, 401]);
});

test('acceso denegado a dashboard sin token C49', function () {
    $response = $this->getJson('/api/pedidos/dashboard');
    $response->assertStatus(401);
});
