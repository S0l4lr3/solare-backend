<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('usuarios')) {
            Schema::create('usuarios', function (Blueprint $table) {
           $table->id(); // Este crea el 'id' bigint(20) UNSIGNED
        $table->string('nombre', 100);
        $table->string('apellido_paterno', 100);
        $table->string('apellido_materno', 100)->nullable();
        $table->string('correo', 100)->unique();
        $table->string('contrasena', 255);
        
        // Llave foránea a la tabla roles
        $table->foreignId('rol_id')->constrained('roles');
        
        // Campos para el login y sistema
        $table->string('token_recuerdo', 100)->nullable();
        $table->datetime('correo_verificado_en')->nullable();
        
        // Timestamps de Laravel (sustituyen a creado_en y actualizado_en)
        $table->timestamps();
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
