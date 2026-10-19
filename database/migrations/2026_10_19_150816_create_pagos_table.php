<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('socio_id')->constrained('socios')->restrictOnDelete();
            // foto del plan al generar el pago
            $table->foreignId('plan_id')->index()->constrained('planes')->restrictOnDelete();
            // siempre día 1 del mes
            $table->date('mes_abonado');
            $table->decimal('monto_original', 10, 2);
            // la carga el admin al cobrar; null = sin mora
            $table->decimal('monto_mora', 10, 2)->nullable();
            $table->date('fecha_vencimiento');
            // "vencido" no se guarda: se calcula
            $table->enum('estado', ['pendiente', 'pagado'])->default('pendiente');
            $table->date('fecha_pago')->nullable();
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'debito', 'credito', 'mercado_pago'])->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            // un pago por socio por mes
            $table->unique(['socio_id', 'mes_abonado']);
            $table->index(['estado', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
