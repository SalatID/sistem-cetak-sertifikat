<?php

namespace App\Models;

use Database\Factories\PesertaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['kontingen_id', 'nama', 'jenis_kelamin', 'kategori', 'kelas', 'kelompok_usia', 'juara', 'no_sertifikat', 'no_sk'])]
class Peserta extends Model
{
    /** @use HasFactory<PesertaFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Peserta $peserta): void {
            if (! $peserta->berhakNomorSertifikat()) {
                $peserta->no_sertifikat = null;
            }
        });
    }

    /**
     * Daftar kelompok usia (menentukan sertifikat prestasi atau pemasalan).
     *
     * @return list<string>
     */
    public static function pilihanKelompokUsia(): array
    {
        return ['Pra Usia Dini', 'Usia Dini 1', 'Usia Dini 2', 'Pra Remaja', 'Remaja', 'Dewasa'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'juara' => 'string',
            'no_sertifikat' => 'integer',
        ];
    }

    public function kontingen(): BelongsTo
    {
        return $this->belongsTo(Kontingen::class);
    }

    /**
     * Filter daftar peserta: pencarian nama, kontingen, kategori, kelompok usia, dan juara.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn (Builder $q, string $value) => $q->where('nama', 'like', "%{$value}%"))
            ->when($filters['kontingen_id'] ?? null, fn (Builder $q, $value) => $q->where('kontingen_id', $value))
            ->when($filters['kategori'] ?? null, fn (Builder $q, string $value) => $q->where('kategori', $value))
            ->when($filters['kelompok_usia'] ?? null, fn (Builder $q, string $value) => $q->where('kelompok_usia', $value))
            ->when($filters['juara'] ?? null, fn (Builder $q, $value) => $value === '-'
                ? $q->whereNull('juara')
                : $q->where('juara', $value));
    }

    /**
     * Saran keterangan juara untuk form (masih bisa diisi teks bebas).
     *
     * @return list<string>
     */
    public static function pilihanJuara(): array
    {
        return ['Peserta', 'Pesilat Terbaik', 'Juara 1', 'Juara 2', 'Juara 3'];
    }

    /**
     * Apakah peserta berhak memperoleh nomor sertifikat.
     *
     * Hanya Juara 1/2/3 dan Pesilat Terbaik. Label "Peserta" (serta nilai
     * lain di luar daftar) tidak diberi nomor sertifikat.
     */
    public function berhakNomorSertifikat(): bool
    {
        $juara = (string) preg_replace('/\s+/', ' ', Str::lower(trim((string) $this->juara)));

        return in_array($juara, [
            '1', '2', '3',
            'juara 1', 'juara 2', 'juara 3',
            'emas', 'perak', 'perunggu',
            'pesilat terbaik',
        ], true);
    }

    /**
     * Apakah sertifikat boleh dicetak dari halaman publik.
     *
     * Semua peserta yang sudah punya keterangan juara boleh dicetak,
     * termasuk label "Peserta". Nomor sertifikat tetap hanya dibuat
     * untuk peserta yang berhak (lihat berhakNomorSertifikat()).
     */
    public function bolehCetakSertifikatPublik(): bool
    {
        return trim((string) $this->juara) !== '';
    }

    /**
     * Label juara siap tampil.
     *
     * Nilai lama 1/2/3 tetap dipetakan ke Emas/Perak/Perunggu; nilai teks
     * seperti "peserta" atau "pesilat terbaik" ditampilkan dalam Title Case.
     */
    public static function labelJuaraValue(?string $juara): string
    {
        $value = trim((string) $juara);

        if ($value === '') {
            return 'Belum Juara';
        }

        return match ($value) {
            '1' => 'Emas',
            '2' => 'Perak',
            '3' => 'Perunggu',
            default => Str::title(Str::lower($value)),
        };
    }

    public function labelJuara(): string
    {
        return self::labelJuaraValue($this->juara);
    }

    /**
     * Nilai juara untuk placeholder {JUARA_ROMAWI}.
     *
     * Angka 1/2/3 menjadi I/II/III; teks dikembalikan apa adanya.
     */
    public function juaraRomawi(): string
    {
        return match ((string) $this->juara) {
            '1' => 'I',
            '2' => 'II',
            '3' => 'III',
            default => (string) $this->juara,
        };
    }
}
