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
        Schema::create('asientos', function (Blueprint $table) {
            
          $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->enum('tipo', ['sistema', 'suscripcion', 'especial', 'egreso'])->default('sistema');
            $table->string('descripcion');
            // Monto siempre en dólares
            $table->decimal('monto_dolares', 12, 2);
            // Monto en bolívares, nullable hasta que se actualice
            $table->decimal('monto_bs', 20, 2)->nullable();
            $table->enum('estado', ['pendiente', 'por_validar', 'pagado', 'moroso'])->default('pendiente');
            
            $table->string('referencia')->nullable();
            $table->string('capture')->nullable();
            $table->date('fecha'); // Ejemplo: 2026-01-15, 2026-02-15, etc.
	        $table->date('fecha_pago')->nullable();
            $table->timestamps();

            // Índices para consultas rápidas
            $table->index(['usuario_id', 'fecha']);
            $table->index(['tipo', 'estado']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asientos');
    }
};
