<?php

namespace Database\Seeders;

use App\Models\Kontingen;
use App\Models\Official;
use App\Models\Peserta;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $kontingens = [
            ['nama' => 'Kontingen Sumbermaneg', 'kota' => 'Kab. X'],
            ['nama' => 'Kontingen Putra Darma', 'kota' => 'Kab. Y'],
            ['nama' => 'Kontingen Garuda', 'kota' => 'Kota Z'],
        ];

        foreach ($kontingens as $data) {
            Kontingen::query()->updateOrCreate(['nama' => $data['nama']], $data);
        }

        $pesertas = [
            ['kontingen' => 'Kontingen Sumbermaneg', 'nama' => 'Muhammad Rizky', 'jenis_kelamin' => 'L', 'kategori' => 'Kata Putra', 'kelas' => null, 'kelompok_usia' => 'Usia Dini 2', 'juara' => 1, 'no_sertifikat' => 1, 'no_sk' => '001/SK/SKX26/III/2026'],
            ['kontingen' => 'Kontingen Sumbermaneg', 'nama' => 'Andi Saputra', 'jenis_kelamin' => 'L', 'kategori' => 'Kumite Putra', 'kelas' => '-60 kg', 'kelompok_usia' => 'Remaja', 'juara' => 2, 'no_sertifikat' => 2, 'no_sk' => '001/SK/SKX26/III/2026'],
            ['kontingen' => 'Kontingen Putra Darma', 'nama' => 'Salsabila Putri', 'jenis_kelamin' => 'P', 'kategori' => 'Kata Putri', 'kelas' => null, 'kelompok_usia' => 'Usia Dini 1', 'juara' => 1, 'no_sertifikat' => null, 'no_sk' => '002/SK/SKX26/III/2026'],
            ['kontingen' => 'Kontingen Putra Darma', 'nama' => 'Nadia Kirana', 'jenis_kelamin' => 'P', 'kategori' => 'Kumite Putri', 'kelas' => '-50 kg', 'kelompok_usia' => 'Dewasa', 'juara' => 3, 'no_sertifikat' => 3, 'no_sk' => '002/SK/SKX26/III/2026'],
            ['kontingen' => 'Kontingen Garuda', 'nama' => 'Febriyansyah', 'jenis_kelamin' => 'L', 'kategori' => 'Kumite Putra', 'kelas' => '-70 kg', 'kelompok_usia' => 'Pra Remaja', 'juara' => null, 'no_sertifikat' => null, 'no_sk' => null],
        ];

        foreach ($pesertas as $data) {
            $kontingen = Kontingen::query()->where('nama', $data['kontingen'])->firstOrFail();

            Peserta::query()->updateOrCreate(
                ['kontingen_id' => $kontingen->id, 'nama' => $data['nama'], 'kategori' => $data['kategori']],
                [
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'kelas' => $data['kelas'],
                    'kelompok_usia' => $data['kelompok_usia'],
                    'juara' => $data['juara'],
                    'no_sertifikat' => $data['no_sertifikat'],
                    'no_sk' => $data['no_sk'],
                ],
            );
        }

        $officials = [
            ['kontingen' => 'Kontingen Sumbermaneg', 'nama' => 'Coach Budi Hartono', 'jabatan' => 'Coach'],
            ['kontingen' => 'Kontingen Putra Darma', 'nama' => 'Coach Darmawan', 'jabatan' => 'Coach'],
            ['kontingen' => 'Kontingen Putra Darma', 'nama' => 'Pak Joko Wibisono', 'jabatan' => 'Official'],
        ];

        foreach ($officials as $data) {
            $kontingen = Kontingen::query()->where('nama', $data['kontingen'])->firstOrFail();

            Official::query()->updateOrCreate(
                ['kontingen_id' => $kontingen->id, 'nama' => $data['nama']],
                ['jabatan' => $data['jabatan']],
            );
        }
    }
}
