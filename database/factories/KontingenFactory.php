<?php

namespace Database\Factories;

use App\Models\Kontingen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kontingen>
 */
class KontingenFactory extends Factory
{
    protected $model = Kontingen::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Kontingen '.fake()->unique()->numerify('###'),
            'kota' => fake()->city(),
        ];
    }
}
