<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Contracts\OAuthenticatable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Usuario extends Authenticatable
{
     use HasApiTokens, HasFactory, Notifiable;

     
     protected $table = 'usuarios'; 
     
     protected $fillable =[
         'nombre',
         'apellido_paterno',
         'apellido_materno',
         'correo',
         'rol_id',
         'contrasena',
         'rol_id',  
     ];

     protected $hidden = [
        'contrasena',
        'token_recuerdo',
    ];

    

     public $timestamps=false;
     
      // 1. Dile a Passport dónde está el correo
      public function getEmailAttribute() {
         return $this->correo;
      }

     public function getAuthPassword()
     {
        return $this->contrasena;   
     }


public function getAuthIdentifier()
{
    // Retorna el ID numérico, no el correo
    return $this->id; 
}
}
