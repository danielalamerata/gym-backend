<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function socios(): HasMany
    {
        return $this->hasMany(Socio::class);
    }
}
