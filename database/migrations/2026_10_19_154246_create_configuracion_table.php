<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // una sola fila; si no hay ninguna se usan los valores por defecto del modelo
        Schema::create('configuracion', function (Blueprint $table) {
            $table->id();
            // 1 a 28, para que el día exista en todos los meses
            $table->unsignedTinyInteger('dia_vencimiento');
            $table->timestamps();
        });

        // red de seguridad en la base; el mensaje claro lo da la validación del controller
        DB::statement('ALTER TABLE configuracion ADD CONSTRAINT configuracion_dia_vencimiento_check CHECK (dia_vencimiento BETWEEN 1 AND 28)');
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};
