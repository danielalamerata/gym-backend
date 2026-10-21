<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['socio_id', 'plan_id', 'mes_abonado', 'monto_original', 'monto_mora', 'fecha_vencimiento', 'estado', 'fecha_pago', 'metodo_pago', 'notas'])]
#[Appends(['vencido', 'total'])]
class Pago extends Model
{
    protected $table = 'pagos';

    protected function casts(): array
    {
        return [
            'mes_abonado' => 'date:Y-m-d',
            'fecha_vencimiento' => 'date:Y-m-d',
            'fecha_pago' => 'date:Y-m-d',
            'monto_original' => 'decimal:2',
            'monto_mora' => 'decimal:2',
        ];
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // vencido = pendiente con fecha_vencimiento anterior a hoy (el día del vencimiento todavía está al día)
    #[Scope]
    protected function vencidos(Builder $query): void
    {
        $query->where('estado', 'pendiente')
            ->whereDate('fecha_vencimiento', '<', today());
    }

    // misma regla que el scope vencidos()
    protected function vencido(): Attribute
    {
        return Attribute::get(
            fn () => $this->estado === 'pendiente' && $this->fecha_vencimiento->lt(today())
        );
    }

    // monto_original + monto_mora (null = sin mora); se suma en centavos para no arrastrar errores de float
    protected function total(): Attribute
    {
        return Attribute::get(function () {
            $centavos = fn (?string $monto) => $monto === null ? 0 : (int) str_replace('.', '', $monto);
            $total = $centavos($this->monto_original) + $centavos($this->monto_mora);

            return sprintf('%d.%02d', intdiv($total, 100), $total % 100);
        });
    }
}
