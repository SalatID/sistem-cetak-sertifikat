<?php

namespace Database\Factories;

use App\Models\Peserta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Peserta>
 */
class PesertaFactory extends Factory
{
    protected $model = Peserta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'kategori' => fake()->randomElement(['Kata Putra', 'Kata Putri', 'Kumite Putra', 'Kumite Putri']),
            'kelompok_usia' => fake()->randomElement(Peserta::pilihanKelompokUsia()),
            'jenis_kelamin' => fake()->randomElement(['L', 'P']),
            'kelas' => null,
            'juara' => fake()->randomElement(['Peserta', 'Pesilat Terbaik', '1', '2', '3', null]),
            'no_sk' => null,
        ];
    }
}
