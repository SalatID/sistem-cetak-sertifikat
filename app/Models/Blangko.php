<?php

namespace App\Models;

use Database\Factories\BlangkoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Fillable(['nama', 'jenis', 'mode', 'view_blade', 'ukuran_kertas', 'gambar', 'orientasi', 'lebar_mm', 'tinggi_mm', 'aktif'])]
class Blangko extends Model
{
    /** @use HasFactory<BlangkoFactory> */
    use HasFactory;

    public const MODE_CANVAS = 'canvas';

    public const MODE_BLADE = 'blade';

    public const JENIS_SERTIFIKAT = 'sertifikat';

    public const JENIS_SK = 'sk';

    public const JENIS_SEKOLAH = 'sertifikat_sekolah';

    /**
     * Pilihan jenis dokumen yang memakai blangko.
     *
     * @return array<string, string>
     */
    public static function pilihanJenis(): array
    {
        return [
            self::JENIS_SERTIFIKAT => 'Sertifikat (peserta & coach)',
            self::JENIS_SK => 'SK Juara',
            self::JENIS_SEKOLAH => 'Sertifikat Sekolah (ucapan terima kasih)',
        ];
    }

    public function labelJenis(): string
    {
        return self::pilihanJenis()[$this->jenis] ?? 'Sertifikat';
    }

    /**
     * Pilihan ukuran kertas standar (dimensi portrait, dalam mm).
     *
     * @return array<string, array{label: string, lebar: int|null, tinggi: int|null}>
     */
    public static function pilihanUkuran(): array
    {
        return [
            'a4' => ['label' => 'A4 (210 × 297 mm)', 'lebar' => 210, 'tinggi' => 297],
            'f4' => ['label' => 'F4 / Folio (215 × 330 mm)', 'lebar' => 215, 'tinggi' => 330],
            'letter' => ['label' => 'Letter (216 × 279 mm)', 'lebar' => 216, 'tinggi' => 279],
            'custom' => ['label' => 'Custom (isi lebar & tinggi manual)', 'lebar' => null, 'tinggi' => null],
        ];
    }

    /**
     * Hitung lebar/tinggi (mm) dari ukuran kertas + orientasi.
     * Mengembalikan null untuk ukuran custom (dimensi diisi manual).
     *
     * @return array{lebar_mm: int, tinggi_mm: int}|null
     */
    public static function dimensiUkuran(string $ukuran, string $orientasi): ?array
    {
        $pilihan = self::pilihanUkuran()[$ukuran] ?? null;

        if ($pilihan === null || $pilihan['lebar'] === null || $pilihan['tinggi'] === null) {
            return null;
        }

        return $orientasi === 'portrait'
            ? ['lebar_mm' => $pilihan['lebar'], 'tinggi_mm' => $pilihan['tinggi']]
            : ['lebar_mm' => $pilihan['tinggi'], 'tinggi_mm' => $pilihan['lebar']];
    }

    /**
     * Label ukuran kertas untuk ditampilkan di UI.
     */
    public function labelUkuran(): string
    {
        return self::pilihanUkuran()[$this->ukuran_kertas]['label'] ?? 'Custom';
    }

    /**
     * Template Blade bawaan untuk halaman admin — tidak boleh dipilih
     * sebagai template cetak.
     *
     * @var list<string>
     */
    private const VIEW_ADMIN = ['index', 'form', 'desainer', 'preview', 'blade'];

    /**
     * Daftar template cetak dari folder resources/views/blangko.
     *
     * @return array<string, string> view => label
     */
    public static function pilihanTemplate(): array
    {
        $folder = resource_path('views/blangko');
        $daftar = [];

        foreach (File::glob($folder.'/*.blade.php') as $path) {
            $nama = basename($path, '.blade.php');

            if (in_array($nama, self::VIEW_ADMIN, true)) {
                continue;
            }

            $daftar['blangko.'.$nama] = Str::headline($nama).' ('.$nama.')';
        }

        foreach (File::glob($folder.'/template/*.blade.php') as $path) {
            $nama = basename($path, '.blade.php');
            $daftar['blangko.template.'.$nama] = Str::headline($nama).' (template/'.$nama.')';
        }

        return $daftar;
    }

    /**
     * Apakah blangko ini memakai file Blade (bukan kanvas)?
     */
    public function pakaiBlade(): bool
    {
        return $this->mode === self::MODE_BLADE && filled($this->view_blade);
    }

    /**
     * Path file template Blade (untuk ditampilkan di editor), atau null.
     */
    public function pathTemplate(): ?string
    {
        if (! $this->pakaiBlade()) {
            return null;
        }

        $relatif = str_replace(['blangko.', '.'], ['', '/'], (string) $this->view_blade);
        $path = resource_path('views/blangko/'.$relatif.'.blade.php');

        return is_file($path) ? $path : null;
    }

    public function elemen(): HasMany
    {
        return $this->hasMany(BlangkoElemen::class)->orderBy('z_index')->orderBy('id');
    }

    public function varian(): HasMany
    {
        return $this->hasMany(TeksVarian::class);
    }

    /**
     * Blangko aktif untuk suatu jenis dokumen (sertifikat / sk / sertifikat sekolah).
     */
    public static function aktif(string $jenis): ?self
    {
        return static::query()
            ->where('jenis', $jenis)
            ->where('aktif', true)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Path absolut gambar blangko (untuk DOMPDF) atau null.
     */
    public function pathGambar(): ?string
    {
        if (blank($this->gambar)) {
            return null;
        }

        $path = public_path('storage/'.ltrim($this->gambar, '/'));

        return is_file($path) ? $path : null;
    }

    /**
     * URL publik gambar blangko (untuk preview di browser).
     */
    public function urlGambar(): ?string
    {
        return blank($this->gambar) ? null : asset('storage/'.ltrim($this->gambar, '/'));
    }
}
