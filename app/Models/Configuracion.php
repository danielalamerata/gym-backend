<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
#[Fillable(['dia_vencimiento'])]
class Configuracion extends Model
{
    protected $table = 'configuracion';

    // se usa mientras el admin no haya guardado un valor
    public const DIA_VENCIMIENTO_DEFAULT = 10;

    protected function casts(): array
    {
        return [
            'dia_vencimiento' => 'integer',
        ];
    }

    public static function diaVencimiento(): int
    {
        return static::query()->orderBy('id')->first()?->dia_vencimiento
            ?? self::DIA_VENCIMIENTO_DEFAULT;
    }

    // crea la fila la primera vez; después actualiza siempre la misma
    public static function actualizarDiaVencimiento(int $dia): void
    {
        $configuracion = static::query()->orderBy('id')->firstOrNew();
        $configuracion->dia_vencimiento = $dia;
        $configuracion->save();
    }
}
