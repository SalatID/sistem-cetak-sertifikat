<?php

namespace App\Models;

use Database\Factories\OfficialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kontingen_id', 'nama', 'jabatan'])]
class Official extends Model
{
    /** @use HasFactory<OfficialFactory> */
    use HasFactory;

    public function kontingen(): BelongsTo
    {
        return $this->belongsTo(Kontingen::class);
    }

    /**
     * Daftar jabatan yang dipakai pada form (P1: belum dipakai, disiapkan untuk P3).
     *
     * @return list<string>
     */
    public static function jabatanOptions(): array
    {
        return ['Coach', 'Official', 'Manager', 'Dokter', 'Lainnya'];
    }
}
