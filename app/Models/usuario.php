<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Contracts\OAuthenticatable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class usuario extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

     
     protected $table = ''; 
     public $timestamps='false';

     public function getAuthPassword()
     {
        return $this->contrasena;   
     }
}
