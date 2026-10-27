<?php

namespace App\Models;

use Database\Factories\TeksVarianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['blangko_id', 'key', 'judul', 'isi'])]
class TeksVarian extends Model
{
    /** @use HasFactory<TeksVarianFactory> */
    use HasFactory;

    public function blangko(): BelongsTo
    {
        return $this->belongsTo(Blangko::class);
    }
}
