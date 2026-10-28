<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'nama_kejuaraan' => 'Silat Benteng Cisadane Championshiip',
            'singkatan_kejuaraan' => 'SKX26',
            'penyelenggara' => 'DPD Karate Kabupaten X',
            'tempat_tanggal' => 'GOR Kabupaten X, 10-12 Maret 2026',
            'teks_sertifikat' => 'Diberikan kepada {NAMA} atas prestasinya sebagai JUARA {JUARA_ROMAWI} pada {KATEGORI}{KELAS} mewakili {KONTINGEN}. No. {NOMOR_SERTIFIKAT}.',
            'teks_sertifikat_official' => 'Diberikan kepada {NAMA} selaku {JABATAN} Kontingen {KONTINGEN}.',
            'nama_ttd' => 'H. Ahmad Wijaya, S.Pd',
            'jabatan_ttd' => 'Ketua Panitia',
            'kota_ttd' => 'X',
            'varian_pemasalan' => 'Pra Usia Dini, Usia Dini 1',
            'legalisir_jumlah_copy' => '2',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value],
            );
        }
    }
}
