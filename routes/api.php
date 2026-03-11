<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/registro', [AuthController::class, 'signIn']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function(){

Route::delete('logout',[AuthController::class,'logout']);
});