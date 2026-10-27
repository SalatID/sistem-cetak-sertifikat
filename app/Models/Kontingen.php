<?php

namespace App\Models;

use Database\Factories\KontingenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'kota'])]
class Kontingen extends Model
{
    /** @use HasFactory<KontingenFactory> */
    use HasFactory;

    public function pesertas(): HasMany
    {
        return $this->hasMany(Peserta::class);
    }

    public function officials(): HasMany
    {
        return $this->hasMany(Official::class);
    }
}
