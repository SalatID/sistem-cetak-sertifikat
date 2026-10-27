<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama', 'terakhir_dicetak_at', 'jumlah_cetak'])]
class Sekolah extends Model
{
    // Nama tabel disetel manual agar tidak bergantung pada pluralisasi inflector.
    protected $table = 'sekolahs';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'terakhir_dicetak_at' => 'datetime',
            'jumlah_cetak' => 'integer',
        ];
    }
}
