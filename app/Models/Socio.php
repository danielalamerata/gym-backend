<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['user_id', 'plan_id', 'estado', 'fecha_inicio', 'notas'])]
class Socio extends Model
{
    protected $table = 'socios';

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
