<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
#[Fillable(['nombre', 'frecuencia_semanal', 'precio', 'activo'])]
class Plan extends Model
{
    protected $table = 'planes';

    protected function casts(): array
    {
        return [
            'frecuencia_semanal' => 'integer',
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }
}
