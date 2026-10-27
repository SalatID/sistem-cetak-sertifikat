<?php

namespace App\Models;

use Database\Factories\BlangkoElemenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'blangko_id', 'label', 'tipe', 'x_mm', 'y_mm', 'lebar_mm', 'tinggi_mm',
    'isi', 'sumber_field', 'varian_key', 'font_family', 'font_size',
    'tebal', 'miring', 'align', 'valign', 'warna', 'z_index', 'tampil',
])]
class BlangkoElemen extends Model
{
    /** @use HasFactory<BlangkoElemenFactory> */
    use HasFactory;

    // Nama tabel disetel manual: pluralisasi otomatis "elemen" -> "elemens".
    protected $table = 'blangko_elemen';

    public function blangko(): BelongsTo
    {
        return $this->belongsTo(Blangko::class);
    }

    /**
     * Nama field data yang bisa dipakai pada elemen bertipe 'data'.
     *
     * @return array<string, string>
     */
    public static function pilihanField(): array
    {
        return [
            'nama' => 'Nama peserta / official',
            'sekolah' => 'Nama sekolah',
            'jenis_kelamin' => 'Jenis kelamin (L/P)',
            'jenis_kelamin_teks' => 'Jenis kelamin (Putra/Putri)',
            'kontingen' => 'Nama kontingen',
            'kategori' => 'Kategori',
            'kelas' => 'Kelas',
            'kelompok_usia' => 'Kelompok usia',
            'juara' => 'Peringkat juara (1/2/3)',
            'juara_romawi' => 'Juara (I/II/III)',
            'label_juara' => 'Label juara (Emas/Perak/Perunggu)',
            'no_sertifikat' => 'Nomor sertifikat',
            'no_sk' => 'Nomor SK',
            'jabatan' => 'Jabatan (official)',
            'tanggal' => 'Tanggal hari ini',
            'nama_kejuaraan' => 'Nama kejuaraan',
            'penyelenggara' => 'Penyelenggara',
            'tempat_tanggal' => 'Tempat & tanggal',
            'kota_ttd' => 'Kota penandatangan',
            'nama_ttd' => 'Nama penandatangan',
            'jabatan_ttd' => 'Jabatan penandatangan',
        ];
    }

    /**
     * @return list<string>
     */
    public static function pilihanFont(): array
    {
        return ['sans', 'serif', 'mono'];
    }
}
